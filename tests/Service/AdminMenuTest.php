<?php

declare(strict_types=1);

namespace Tourze\OpenAITaskBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\OpenAITaskBundle\Controller\Admin\TaskCrudController;
use Tourze\OpenAITaskBundle\Service\AdminMenu;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 无需额外设置
    }

    private function createAdminMenu(): AdminMenu
    {
        return self::getService(AdminMenu::class);
    }

    public function testAdminMenuImplementsInterface(): void
    {
        $adminMenu = $this->createAdminMenu();
        $reflection = new \ReflectionClass($adminMenu);

        $this->assertTrue($reflection->implementsInterface('Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface'));
    }

    public function testAdminMenuConstructor(): void
    {
        $adminMenu = $this->createAdminMenu();
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
    }

    public function testInvokeWithExistingAiCenter(): void
    {
        // 创建Mock LinkGenerator来避免真实的路由生成
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->expects($this->once())
            ->method('getCurdListPage')
            ->with(TaskCrudController::class)
            ->willReturn('/mock-task-list-url')
        ;

        // 将Mock注入到容器中，然后从容器获取服务实例
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        // 创建Mock对象来模拟现有的AI中心菜单项
        $aiCenter = $this->createMock(ItemInterface::class);
        $aiCenter->expects($this->once())
            ->method('addChild')
            ->with('任务管理')
            ->willReturnSelf()
        ;

        // 创建Mock对象来模拟根菜单项，返回现有的AI中心
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('AI智能')
            ->willReturn($aiCenter)
        ;

        $adminMenu->__invoke($rootItem);
    }

    public function testInvokeWithoutAiCenter(): void
    {
        // 创建Mock LinkGenerator来避免真实的路由生成
        $linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        $linkGenerator->expects($this->once())
            ->method('getCurdListPage')
            ->with(TaskCrudController::class)
            ->willReturn('/mock-task-list-url')
        ;

        // 将Mock注入到容器中，然后从容器获取服务实例
        self::getContainer()->set(LinkGeneratorInterface::class, $linkGenerator);
        $adminMenu = self::getService(AdminMenu::class);

        // 创建Mock对象来模拟新创建的AI中心菜单项
        $newAiCenter = $this->createMock(ItemInterface::class);
        $newAiCenter->expects($this->once())
            ->method('addChild')
            ->with('任务管理')
            ->willReturnSelf()
        ;

        // 创建Mock对象来模拟根菜单项，没有现有的AI中心（返回null），然后创建新的AI中心
        $rootItem = $this->createMock(ItemInterface::class);
        $rootItem->expects($this->exactly(2))
            ->method('getChild')
            ->with('AI智能')
            ->willReturnOnConsecutiveCalls(null, $newAiCenter)
        ;

        $rootItem->expects($this->once())
            ->method('addChild')
            ->with('AI智能')
            ->willReturn($newAiCenter)
        ;

        $adminMenu->__invoke($rootItem);
    }
}
