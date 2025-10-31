<?php

namespace Tourze\OpenAITaskBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Enum\TaskStatus;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\StreamRequestOptions;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\OpenAITaskBundle\Entity\Task;
use Tourze\OpenAITaskBundle\Exception\ApiKeyNotConfiguredException;
use Tourze\OpenAITaskBundle\Exception\TaskExecutionException;
use Tourze\OpenAITaskBundle\Repository\TaskRepository;

#[AsCommand(name: self::NAME, description: '执行 AI 任务', help: <<<'TXT'
    该命令用于执行 AI 任务。

    使用方法:
      <info>php bin/console open-ai:task -t 1</info> - 执行指定的任务

    示例:
      php bin/console open-ai:task -t 1 -d
    TXT)]
#[Autoconfigure(public: true)]
class TaskCommand extends Command
{
    public const NAME = 'open-ai:task';

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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getOption('task');
        if (null === $taskId || '' === $taskId) {
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
            if (null === $executorApiKey) {
                throw new ApiKeyNotConfiguredException('执行者未配置 API 密钥');
            }
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
            if (null === $managerApiKey) {
                throw new ApiKeyNotConfiguredException('负责人未配置 API 密钥');
            }
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
            $executorConversation = $this->conversationService->initConversation($task->getExecutor(), $executorApiKey);
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
            $managerConversation = $this->conversationService->initConversation($task->getManager(), $managerApiKey);
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
            $executorSystemPrompt = $executorConversation->getSystemPrompt();
            $managerSystemPrompt = $managerConversation->getSystemPrompt();
            if (null !== $executorSystemPrompt) {
                $this->conversationService->createSystemMessage(
                    $executorConversation,
                    $executorApiKey,
                    $executorSystemPrompt
                );
            }
            if (null !== $managerSystemPrompt) {
                $this->conversationService->createSystemMessage(
                    $managerConversation,
                    $managerApiKey,
                    $managerSystemPrompt
                );
            }

            // 开始执行任务
            $this->executeTask($task, $output, (bool) $input->getOption('debug'));

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
        $this->validateTaskConversations($task);

        $executionCommand = '开始执行任务';
        $round = 0;
        $maxRounds = 50;

        while ($round < $maxRounds) {
            ++$round;
            $output->writeln(sprintf("\n<info>第 %d 轮</info>", $round));

            $executorResponse = $this->executeExecutorRound($task, $executionCommand, $output, $debug);
            $managerResponse = $this->executeManagerRound($task, $executorResponse, $output, $debug);

            $result = $this->processManagerResponse($task, $managerResponse, $output, $debug);
            if ($result['completed']) {
                return;
            }

            $executionCommand = $result['nextCommand'];
            $this->entityManager->flush();
        }

        $this->handleMaxRoundsExceeded($task);
    }

    private function validateTaskConversations(Task $task): void
    {
        $executorConversation = $task->getExecutorConversation();
        $managerConversation = $task->getManagerConversation();

        if (null === $executorConversation || null === $managerConversation) {
            throw new TaskExecutionException('对话未初始化');
        }
    }

    private function executeExecutorRound(Task $task, string $executionCommand, OutputInterface $output, bool $debug): string
    {
        $output->writeln("\n<comment>执行者发言</comment>");

        $executor = $task->getExecutor();
        $executorConversation = $task->getExecutorConversation();
        $executorApiKey = $executor->getPreferredApiKey();

        if (null === $executorApiKey) {
            throw new ApiKeyNotConfiguredException('执行者未配置 API 密钥');
        }

        if (null === $executorConversation) {
            throw new TaskExecutionException('执行者对话未初始化');
        }

        $this->conversationService->createMessage(
            $executorConversation,
            $executorApiKey,
            RoleEnum::user,
            $executionCommand
        );

        return $this->getAiResponse($executorConversation, $executor, RoleEnum::assistant, $debug, $output);
    }

    private function executeManagerRound(Task $task, string $executorResponse, OutputInterface $output, bool $debug): string
    {
        $output->writeln("\n<comment>负责人发言</comment>");

        $manager = $task->getManager();
        $managerConversation = $task->getManagerConversation();
        $managerApiKey = $manager->getPreferredApiKey();

        if (null === $managerApiKey) {
            throw new ApiKeyNotConfiguredException('负责人未配置 API 密钥');
        }

        if (null === $managerConversation) {
            throw new TaskExecutionException('负责人对话未初始化');
        }

        $this->conversationService->createMessage(
            $managerConversation,
            $managerApiKey,
            RoleEnum::user,
            "执行者的输出：\n{$executorResponse}\n\n请评估并给出指令。"
        );

        return $this->getAiResponse($managerConversation, $manager, RoleEnum::assistant, $debug, $output);
    }

    /**
     * @return array{completed: bool, nextCommand: string}
     */
    private function processManagerResponse(Task $task, string $managerResponse, OutputInterface $output, bool $debug): array
    {
        $matches = [];
        if (1 !== preg_match('/^(continue|task_done|task_failed)[:\s]*(.*)/i', $managerResponse, $matches)) {
            return ['completed' => false, 'nextCommand' => '继续执行任务'];
        }

        $nextCommand = trim('' !== $matches[2] ? $matches[2] : '继续执行任务');
        $action = strtolower($matches[1]);

        switch ($action) {
            case 'task_done':
                $this->completeTask($task, $output, $debug);

                return ['completed' => true, 'nextCommand' => $nextCommand];

            case 'task_failed':
                $this->failTask($task, $output);

                return ['completed' => true, 'nextCommand' => $nextCommand];

            case 'continue':
            default:
                return ['completed' => false, 'nextCommand' => $nextCommand];
        }
    }

    private function completeTask(Task $task, OutputInterface $output, bool $debug): void
    {
        $output->writeln("\n<comment>请负责人总结任务结果</comment>");

        $manager = $task->getManager();
        $managerConversation = $task->getManagerConversation();
        $managerApiKey = $manager->getPreferredApiKey();

        if (null === $managerApiKey) {
            throw new ApiKeyNotConfiguredException('负责人未配置 API 密钥');
        }

        if (null === $managerConversation) {
            throw new TaskExecutionException('负责人对话未初始化');
        }

        $this->conversationService->createMessage(
            $managerConversation,
            $managerApiKey,
            RoleEnum::user,
            '请根据对话内容，输出最终任务成果，不丢失细节，不需要任何解释。这个阶段不要出现任何task_done/task_failed/continue相关指令。'
        );

        $summaryResponse = $this->getAiResponse($managerConversation, $manager, RoleEnum::assistant, $debug, $output);

        $task->setStatus(TaskStatus::COMPLETED);
        $task->setResult($summaryResponse);
        $this->entityManager->flush();
        $output->writeln("\n<info>任务完成！</info>");
    }

    private function failTask(Task $task, OutputInterface $output): void
    {
        $task->setStatus(TaskStatus::FAILED);
        $this->entityManager->flush();
        $output->writeln("\n<error>任务失败！</error>");
    }

    private function handleMaxRoundsExceeded(Task $task): void
    {
        $task->setStatus(TaskStatus::FAILED);
        $this->entityManager->flush();
        throw new TaskExecutionException('任务超过最大轮次限制仍未完成');
    }

    private function getAiResponse(Conversation $conversation, Character $character, RoleEnum $role, bool $debug, OutputInterface $output): string
    {
        $messages = $this->buildMessages($conversation);
        $apiKey = $this->getCharacterApiKey($character);
        $options = $this->buildStreamOptions($character, $apiKey, $debug);

        return $this->processStreamResponse($apiKey, $messages, $options, $output);
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(Conversation $conversation): array
    {
        $messages = [];
        foreach ($conversation->getMessages() as $message) {
            $messages[] = [
                'role' => $message->getRole()->value,
                'content' => $message->getContent(),
            ];
        }

        return $messages;
    }

    private function getCharacterApiKey(Character $character): ApiKey
    {
        $apiKey = $character->getPreferredApiKey();
        if (null === $apiKey) {
            throw new ApiKeyNotConfiguredException('角色未配置 API 密钥');
        }

        return $apiKey;
    }

    private function buildStreamOptions(Character $character, ApiKey $apiKey, bool $debug): StreamRequestOptions
    {
        $tools = (true === $apiKey->isFunctionCalling()) ? $this->functionService->generateToolsArray($character) : [];

        $toolsParam = [] !== $tools ? $tools : null;

        return new StreamRequestOptions(
            debug: $debug,
            model: $apiKey->getModel(),
            temperature: $character->getTemperature(),
            topP: $character->getTopP(),
            maxTokens: $character->getMaxTokens(),
            presencePenalty: $character->getPresencePenalty(),
            frequencyPenalty: $character->getFrequencyPenalty(),
            // @phpstan-ignore-next-line argument.type
            tools: $toolsParam,
        );
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     */
    private function processStreamResponse(ApiKey $apiKey, array $messages, StreamRequestOptions $options, OutputInterface $output): string
    {
        $response = '';

        foreach ($this->openAiService->streamReasoner($apiKey, $messages, $options) as $chunk) {
            foreach ($chunk->getChoices() as $choice) {
                if (null !== $choice->getContent()) {
                    $content = $choice->getContent();
                    $response .= $content;
                    $output->write($content);
                }
            }
        }

        $output->writeln('');

        return $response;
    }
}
