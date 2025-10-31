<?php

namespace Tourze\OpenAITaskBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OpenAITaskBundle\Exception\TaskExecutionException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(TaskExecutionException::class)]
final class TaskExecutionExceptionTest extends AbstractExceptionTestCase
{
}
