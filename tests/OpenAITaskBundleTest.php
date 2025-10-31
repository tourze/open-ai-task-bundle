<?php

declare(strict_types=1);

namespace Tourze\OpenAITaskBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OpenAITaskBundle\OpenAITaskBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAITaskBundle::class)]
#[RunTestsInSeparateProcesses]
final class OpenAITaskBundleTest extends AbstractBundleTestCase
{
}
