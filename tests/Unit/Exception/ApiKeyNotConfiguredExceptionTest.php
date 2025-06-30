<?php

namespace Tourze\OpenAITaskBundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\OpenAITaskBundle\Exception\ApiKeyNotConfiguredException;

class ApiKeyNotConfiguredExceptionTest extends TestCase
{
    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = new ApiKeyNotConfiguredException();
        
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testExceptionWithMessage(): void
    {
        $message = 'API密钥未配置';
        $exception = new ApiKeyNotConfiguredException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $code = 1001;
        $exception = new ApiKeyNotConfiguredException('测试消息', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
}