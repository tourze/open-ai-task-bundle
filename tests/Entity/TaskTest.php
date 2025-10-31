<?php

namespace Tourze\OpenAITaskBundle\Tests\Entity;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\OpenAITaskBundle\Entity\Task;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Task::class)]
final class TaskTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        // 创建匿名类替代Mock对象
        $executor = new class extends Character {
            public function __toString(): string
            {
                return 'test-executor';
            }
        };
        $manager = new class extends Character {
            public function __toString(): string
            {
                return 'test-manager';
            }
        };

        $task = new Task();
        // 通过反射设置必需的字段
        $reflection = new \ReflectionClass($task);

        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($task, '测试任务');

        $requirementsProperty = $reflection->getProperty('requirements');
        $requirementsProperty->setAccessible(true);
        $requirementsProperty->setValue($task, '测试要求');

        $executorProperty = $reflection->getProperty('executor');
        $executorProperty->setAccessible(true);
        $executorProperty->setValue($task, $executor);

        $managerProperty = $reflection->getProperty('manager');
        $managerProperty->setAccessible(true);
        $managerProperty->setValue($task, $manager);

        return $task;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '测试任务'];
        yield 'requirements' => ['requirements', '完成指定的测试任务'];
        yield 'valid' => ['valid', true];
        yield 'valid-false' => ['valid', false];
        yield 'result' => ['result', '任务执行结果'];
    }

    public function testGetAndSetStatus(): void
    {
        /** @var Task $task */
        $task = $this->createEntity();
        $status = TaskStatus::RUNNING;
        $task->setStatus($status);

        $this->assertSame($status, $task->getStatus());
    }

    public function testDefaultStatus(): void
    {
        /** @var Task $task */
        $task = $this->createEntity();
        $this->assertSame(TaskStatus::PENDING, $task->getStatus());
    }

    public function testDefaultValid(): void
    {
        /** @var Task $task */
        $task = $this->createEntity();
        $this->assertFalse($task->isValid());
    }

    public function testToString(): void
    {
        /** @var Task $task */
        $task = $this->createEntity();
        $task->setName('测试任务');

        $result = (string) $task;
        $this->assertSame('', $result);
    }
}
