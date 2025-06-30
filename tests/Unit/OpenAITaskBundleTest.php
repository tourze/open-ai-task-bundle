<?php

namespace Tourze\OpenAITaskBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tourze\OpenAITaskBundle\OpenAITaskBundle;

class OpenAITaskBundleTest extends TestCase
{
    public function testGetBundleDependencies(): void
    {
        $dependencies = OpenAITaskBundle::getBundleDependencies();
        
        $this->assertArrayHasKey(\Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineSnowflakeBundle\DoctrineSnowflakeBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle::class, $dependencies);
        $this->assertArrayHasKey(\Tourze\DoctrineTrackBundle\DoctrineTrackBundle::class, $dependencies);
    }

    public function testBundleImplementsBundleDependencyInterface(): void
    {
        $bundle = new OpenAITaskBundle();
        
        $this->assertInstanceOf(\Tourze\BundleDependency\BundleDependencyInterface::class, $bundle);
    }

    public function testBundleExtendsSymfonyBundle(): void
    {
        $bundle = new OpenAITaskBundle();
        
        $this->assertInstanceOf(\Symfony\Component\HttpKernel\Bundle\Bundle::class, $bundle);
    }
}