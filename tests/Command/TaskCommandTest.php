<?php

namespace Tourze\OpenAITaskBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\OpenAITaskBundle\Command\TaskCommand;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * TaskCommand 集成测试
 *
 * 测试命令的基本功能和集成行为
 *
 * @internal
 */
#[CoversClass(TaskCommand::class)]
#[RunTestsInSeparateProcesses] final class TaskCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 命令测试不需要特殊的初始化逻辑
    }

    protected function getCommandTester(): CommandTester
    {
        /** @var TaskCommand $command */
        $command = self::getContainer()->get(TaskCommand::class);

        return new CommandTester($command);
    }

    public function testCommandExistsInContainer(): void
    {
        /** @var TaskCommand $command */
        $command = self::getContainer()->get(TaskCommand::class);
        $this->assertInstanceOf(TaskCommand::class, $command);
    }

    public function testCommandHasCorrectName(): void
    {
        /** @var TaskCommand $command */
        $command = self::getContainer()->get(TaskCommand::class);
        $this->assertSame('open-ai:task', $command->getName());
    }

    public function testCommandWithoutTaskId(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([]);

        $this->assertSame(TaskCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('请使用 -t 或 --task 选项指定任务 ID', $commandTester->getDisplay());
    }

    public function testOptionTask(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute(['--task' => '999999']);

        $this->assertSame(TaskCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('任务不存在', $commandTester->getDisplay());
    }

    public function testOptionDebug(): void
    {
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            '--task' => '999999',
            '--debug' => true,
        ]);

        $this->assertSame(TaskCommand::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('任务不存在', $commandTester->getDisplay());
    }
}
