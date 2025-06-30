<?php

namespace Tourze\OpenAITaskBundle\Tests\Unit\Entity;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\TaskStatus;
use PHPUnit\Framework\TestCase;
use Tourze\OpenAITaskBundle\Entity\Task;

class TaskTest extends TestCase
{
    private Task $task;

    protected function setUp(): void
    {
        $this->task = new Task();
    }

    public function testGetAndSetName(): void
    {
        $name = '测试任务';
        $this->task->setName($name);
        
        $this->assertSame($name, $this->task->getName());
    }

    public function testGetAndSetRequirements(): void
    {
        $requirements = '完成指定的测试任务';
        $this->task->setRequirements($requirements);
        
        $this->assertSame($requirements, $this->task->getRequirements());
    }

    public function testGetAndSetValid(): void
    {
        $this->task->setValid(true);
        $this->assertTrue($this->task->isValid());
        
        $this->task->setValid(false);
        $this->assertFalse($this->task->isValid());
    }

    public function testGetAndSetStatus(): void
    {
        $status = TaskStatus::RUNNING;
        $this->task->setStatus($status);
        
        $this->assertSame($status, $this->task->getStatus());
    }

    public function testGetAndSetResult(): void
    {
        $result = '任务执行结果';
        $this->task->setResult($result);
        
        $this->assertSame($result, $this->task->getResult());
    }

    public function testGetAndSetExecutor(): void
    {
        $executor = $this->createMock(Character::class);
        $this->task->setExecutor($executor);
        
        $this->assertSame($executor, $this->task->getExecutor());
    }

    public function testGetAndSetManager(): void
    {
        $manager = $this->createMock(Character::class);
        $this->task->setManager($manager);
        
        $this->assertSame($manager, $this->task->getManager());
    }

    public function testGetAndSetExecutorConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $this->task->setExecutorConversation($conversation);
        
        $this->assertSame($conversation, $this->task->getExecutorConversation());
    }

    public function testGetAndSetManagerConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $this->task->setManagerConversation($conversation);
        
        $this->assertSame($conversation, $this->task->getManagerConversation());
    }

    public function testToStringWithEmptyId(): void
    {
        $this->task->setName('测试任务');
        
        $result = (string) $this->task;
        $this->assertSame('', $result);
    }

    public function testToStringLogic(): void
    {
        $name = '测试任务';
        $this->task->setName($name);
        
        $result = (string) $this->task;
        $this->assertSame('', $result);
    }

    public function testDefaultStatus(): void
    {
        $this->assertSame(TaskStatus::PENDING, $this->task->getStatus());
    }

    public function testDefaultValid(): void
    {
        $this->assertFalse($this->task->isValid());
    }
}