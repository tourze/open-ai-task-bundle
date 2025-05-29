<?php

namespace Tourze\OpenAITaskBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Enum\TaskStatus;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\StreamChunkVO;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\OpenAITaskBundle\Entity\Task;
use Tourze\OpenAITaskBundle\Repository\TaskRepository;

#[AsCommand(
    name: 'open-ai:task',
    description: '执行 AI 任务',
)]
class TaskCommand extends Command
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly TaskRepository $taskRepository,
        private readonly ConversationService $conversationService,
        private readonly EntityManagerInterface $entityManager,
        private readonly FunctionService $functionService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'task',
                't',
                InputOption::VALUE_REQUIRED,
                '任务 ID'
            )
            ->addOption(
                'debug',
                'd',
                InputOption::VALUE_NONE,
                '开启调试模式'
            )
            ->setHelp(<<<'HELP'
该命令用于执行 AI 任务。

使用方法:
  <info>php bin/console open-ai:task -t 1</info> - 执行指定的任务

示例:
  php bin/console open-ai:task -t 1 -d
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getOption('task');
        if (!$taskId) {
            $output->writeln('<error>请使用 -t 或 --task 选项指定任务 ID</error>');

            return Command::FAILURE;
        }

        $task = $this->taskRepository->find($taskId);
        if (!$task instanceof Task) {
            $output->writeln('<error>任务不存在</error>');

            return Command::FAILURE;
        }

        //        if ($task->getStatus() !== TaskStatus::PENDING) {
        //            $output->writeln('<error>任务状态不正确</error>');
        //            return Command::FAILURE;
        //        }

        try {
            $task->setStatus(TaskStatus::RUNNING);
            $task->setResult(null);
            $this->entityManager->flush();

            // 打印任务信息
            $output->writeln(sprintf(
                "\n<comment>任务名称：%s</comment>",
                $task->getName()
            ));
            $output->writeln(sprintf(
                "<comment>任务要求：%s</comment>\n",
                $task->getRequirements()
            ));

            // 打印执行者信息
            $executor = $task->getExecutor();
            $executorApiKey = $executor->getPreferredApiKey();
            $output->writeln(sprintf(
                '<comment>执行者：%s</comment>',
                $executor->getName()
            ));
            $output->writeln(sprintf(
                '<comment>使用密钥：%s</comment>',
                $executorApiKey->getTitle(),
            ));
            $output->writeln(sprintf(
                "<comment>使用模型：%s</comment>\n",
                $executorApiKey->getModel(),
            ));

            // 打印负责人信息
            $manager = $task->getManager();
            $managerApiKey = $manager->getPreferredApiKey();
            $output->writeln(sprintf(
                '<comment>负责人：%s</comment>',
                $manager->getName()
            ));
            $output->writeln(sprintf(
                '<comment>使用密钥：%s</comment>',
                $managerApiKey->getTitle(),
            ));
            $output->writeln(sprintf(
                "<comment>使用模型：%s</comment>\n",
                $managerApiKey->getModel(),
            ));

            // 创建执行者对话
            $executorConversation = $this->conversationService->initConversation($task->getExecutor(), $task->getExecutor()->getPreferredApiKey());
            $executorConversation->setTitle("任务执行：{$task->getName()}");
            $executorConversation->setSystemPrompt(sprintf(
                "这是一个任务执行过程。任务要求：%s\n\n" .
                "你是执行者，需要按照要求完成任务。\n" .
                "每次我给你一个指令，你需要按照指令执行并输出结果。\n" .
                '初始指令是：开始执行任务',
                $task->getRequirements()
            ));
            $task->setExecutorConversation($executorConversation);

            // 创建负责人对话
            $managerConversation = $this->conversationService->initConversation($task->getManager(), $task->getManager()->getPreferredApiKey());
            $managerConversation->setTitle("任务管理：{$task->getName()}");
            $managerConversation->setSystemPrompt(sprintf(
                "这是一个任务管理过程。任务要求：%s\n\n" .
                "你是负责人，需要管理和评估执行者的工作。\n" .
                "每次我会把执行者的输出发给你，你需要评估并给出下一步指令。\n" .
                "你的输出必须以以下指令之一开头：\n" .
                "- continue: 继续执行任务\n" .
                "- task_done: 任务已完成\n" .
                "- task_failed: 任务失败\n\n" .
                '在指令后面，你可以补充具体的要求或建议。',
                $task->getRequirements()
            ));
            $task->setManagerConversation($managerConversation);

            // 系统消息
            $this->conversationService->createSystemMessage(
                $executorConversation,
                $task->getExecutor()->getPreferredApiKey(),
                $executorConversation->getSystemPrompt()
            );
            $this->conversationService->createSystemMessage(
                $managerConversation,
                $task->getManager()->getPreferredApiKey(),
                $managerConversation->getSystemPrompt()
            );

            // 开始执行任务
            $this->executeTask($task, $output, $input->getOption('debug'));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $task->setStatus(TaskStatus::FAILED);
            $this->entityManager->flush();

            $output->writeln(sprintf('<error>%s</error>', $e));

            return Command::FAILURE;
        }
    }

    private function executeTask(Task $task, OutputInterface $output, bool $debug): void
    {
        $executor = $task->getExecutor();
        $manager = $task->getManager();
        $executorConversation = $task->getExecutorConversation();
        $managerConversation = $task->getManagerConversation();
        $executionCommand = '开始执行任务';
        $round = 0;
        $maxRounds = 50; // 最大轮次限制

        while ($round < $maxRounds) {
            ++$round;
            $output->writeln(sprintf("\n<info>第 %d 轮</info>", $round));

            // 执行者回合 - 接收指令并执行
            $output->writeln("\n<comment>执行者发言</comment>");
            // $output->writeln("<info>当前指令: {$executionCommand}</info>");

            // 创建用户消息(指令)
            $this->conversationService->createMessage(
                $executorConversation,
                $executor->getPreferredApiKey(),
                RoleEnum::user,
                $executionCommand
            );

            // 获取执行者响应
            $executorResponse = $this->getAiResponse($executorConversation, $executor, RoleEnum::assistant, $debug, $output);

            // 负责人回合 - 评估执行结果并给出指令
            $output->writeln("\n<comment>负责人发言</comment>");

            // 创建用户消息(执行结果)
            $this->conversationService->createMessage(
                $managerConversation,
                $manager->getPreferredApiKey(),
                RoleEnum::user,
                "执行者的输出：\n{$executorResponse}\n\n请评估并给出指令。"
            );

            // 获取负责人响应
            $managerResponse = $this->getAiResponse($managerConversation, $manager, RoleEnum::assistant, $debug, $output);

            // 解析负责人的指令
            if (preg_match('/^(continue|task_done|task_failed)[:\s]*(.*)/i', $managerResponse, $matches)) {
                $executionCommand = trim($matches[2] ?: '继续执行任务');

                switch (strtolower($matches[1])) {
                    case 'task_done':
                        // 让负责人做最终总结
                        $output->writeln("\n<comment>请负责人总结任务结果</comment>");
                        $this->conversationService->createMessage(
                            $managerConversation,
                            $manager->getPreferredApiKey(),
                            RoleEnum::user,
                            '请根据对话内容，输出最终任务成果，不丢失细节，不需要任何解释。这个阶段不要出现任何task_done/task_failed/continue相关指令。'
                        );
                        $summaryResponse = $this->getAiResponse($managerConversation, $manager, RoleEnum::assistant, $debug, $output);

                        $task->setStatus(TaskStatus::COMPLETED);
                        $task->setResult($summaryResponse);
                        $this->entityManager->flush();
                        $output->writeln("\n<info>任务完成！</info>");

                        return;

                    case 'task_failed':
                        $task->setStatus(TaskStatus::FAILED);
                        $this->entityManager->flush();
                        $output->writeln("\n<error>任务失败！</error>");

                        return;

                    case 'continue':
                    default:
                        // 继续下一轮
                        break;
                }
            } else {
                $executionCommand = '继续执行任务';
            }

            $this->entityManager->flush();
        }

        // 超过最大轮次限制
        $task->setStatus(TaskStatus::FAILED);
        $this->entityManager->flush();
        throw new \RuntimeException('任务超过最大轮次限制仍未完成');
    }

    private function getAiResponse(Conversation $conversation, Character $character, RoleEnum $role, bool $debug, OutputInterface $output): string
    {
        $messages = [];
        foreach ($conversation->getMessages() as $message) {
            $messages[] = [
                'role' => $message->getRole()->value,
                'content' => $message->getContent(),
            ];
        }

        $apiKey = $character->getPreferredApiKey();
        if (!$apiKey) {
            throw new \RuntimeException('角色未配置 API 密钥');
        }

        $options = [
            'debug' => $debug,
            'model' => $apiKey->getModel(),
            'temperature' => $character->getTemperature(),
            'top_p' => $character->getTopP(),
            'max_tokens' => $character->getMaxTokens(),
            'presence_penalty' => $character->getPresencePenalty(),
            'frequency_penalty' => $character->getFrequencyPenalty(),
        ];

        $tools = $apiKey->isFunctionCalling() ? $this->functionService->generateToolsArray($character) : [];
        if (!empty($tools)) {
            $options['tools'] = $tools;
        }

        $response = '';
        $message = null;

        foreach ($this->openAiService->streamReasoner($apiKey, $messages, $options) as $chunk) {
            if (!$chunk instanceof StreamChunkVO) {
                continue;
            }

            foreach ($chunk->getChoices() as $choice) {
                if (null !== $choice->getContent()) {
                    $content = $choice->getContent();
                    $response .= $content;
                    // 实时输出内容
                    $output->write($content);
                }
            }
        }

        // 最后输出一个换行
        $output->writeln('');

        return $response;
    }
}
