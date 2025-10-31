<?php

namespace Tourze\OpenAITaskBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OpenAITaskBundle\Exception\ApiKeyNotConfiguredException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(ApiKeyNotConfiguredException::class)]
final class ApiKeyNotConfiguredExceptionTest extends AbstractExceptionTestCase
{
}
