<?php

namespace Tourze\OpenAITaskBundle\Tests\Integration\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OpenAITaskBundle\Command\TaskCommand;

class TaskCommandTest extends TestCase
{
    public function testExecuteWithoutTaskId(): void
    {
        $application = new Application();
        $application->add(new TaskCommand(
            $this->createMock(\OpenAIBundle\Service\OpenAiService::class),
            $this->createMock(\Tourze\OpenAITaskBundle\Repository\TaskRepository::class),
            $this->createMock(\OpenAIBundle\Service\ConversationService::class),
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
            $this->createMock(\OpenAIBundle\Service\FunctionService::class),
        ));

        $command = $application->find('open-ai:task');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $this->assertSame(TaskCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('请使用 -t 或 --task 选项指定任务 ID', $commandTester->getDisplay());
    }

    public function testExecuteWithNonExistentTask(): void
    {
        $taskRepository = $this->createMock(\Tourze\OpenAITaskBundle\Repository\TaskRepository::class);
        $taskRepository->method('find')->willReturn(null);

        $application = new Application();
        $application->add(new TaskCommand(
            $this->createMock(\OpenAIBundle\Service\OpenAiService::class),
            $taskRepository,
            $this->createMock(\OpenAIBundle\Service\ConversationService::class),
            $this->createMock(\Doctrine\ORM\EntityManagerInterface::class),
            $this->createMock(\OpenAIBundle\Service\FunctionService::class),
        ));

        $command = $application->find('open-ai:task');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--task' => '999']);

        $this->assertSame(TaskCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('任务不存在', $commandTester->getDisplay());
    }
}