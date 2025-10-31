<?php

namespace Tourze\OpenAITaskBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\OpenAITaskBundle\DependencyInjection\OpenAITaskExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAITaskExtension::class)]
final class OpenAITaskExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
}
