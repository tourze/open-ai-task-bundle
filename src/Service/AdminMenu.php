<?php

declare(strict_types=1);

namespace Tourze\OpenAITaskBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\OpenAITaskBundle\Controller\Admin\TaskCrudController;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(private LinkGeneratorInterface $linkGenerator)
    {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('AI智能')) {
            $item->addChild('AI智能')->setExtra('permission', 'OpenAIBundle');
        }

        $aiCenter = $item->getChild('AI智能');
        if (null !== $aiCenter) {
            $aiCenter->addChild('任务管理')->setUri($this->linkGenerator->getCurdListPage(TaskCrudController::class));
        }
    }
}
