<?php

namespace Tourze\OpenAITaskBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\OpenAITaskBundle\Exception\TaskExecutionException;

class TaskExecutionExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new TaskExecutionException();
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = '任务执行失败';
        $exception = new TaskExecutionException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $code = 2001;
        $exception = new TaskExecutionException('测试消息', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
}