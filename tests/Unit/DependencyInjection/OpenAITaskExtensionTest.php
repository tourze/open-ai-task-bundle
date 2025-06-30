<?php

namespace Tourze\OpenAITaskBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\OpenAITaskBundle\DependencyInjection\OpenAITaskExtension;

class OpenAITaskExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $extension = new OpenAITaskExtension();
        $container = new ContainerBuilder();

        $extension->load([], $container);

        $this->assertTrue(true);
    }

    public function testGetAlias(): void
    {
        $extension = new OpenAITaskExtension();
        
        $this->assertSame('open_ai_task', $extension->getAlias());
    }
}