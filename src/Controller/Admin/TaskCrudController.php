<?php

declare(strict_types=1);

namespace Tourze\OpenAITaskBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use OpenAIBundle\Enum\TaskStatus;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\OpenAITaskBundle\Entity\Task;

#[AdminCrud(
    routePath: '/open-ai-task/task',
    routeName: 'open_ai_task_task'
)]
final class TaskCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Task::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('AI任务')
            ->setEntityLabelInPlural('AI任务管理')
            ->setPageTitle(Crud::PAGE_INDEX, 'AI任务列表')
            ->setPageTitle(Crud::PAGE_NEW, '新建AI任务')
            ->setPageTitle(Crud::PAGE_EDIT, '编辑AI任务')
            ->setPageTitle(Crud::PAGE_DETAIL, 'AI任务详情')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['name', 'requirements', 'result'])
            ->showEntityActionsInlined()
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->onlyOnIndex()
        ;

        yield TextField::new('name', '任务名称')
            ->setRequired(true)
            ->setMaxLength(50)
        ;

        yield TextareaField::new('requirements', '任务要求')
            ->setRequired(true)
            ->setMaxLength(65535)
            ->setHelp('详细描述任务的具体要求和目标')
        ;

        yield AssociationField::new('executor', '执行者')
            ->setRequired(true)
            ->setHelp('负责执行此任务的AI角色')
        ;

        yield AssociationField::new('manager', '管理者')
            ->setRequired(true)
            ->setHelp('负责管理和监督此任务的AI角色')
        ;

        $statusField = EnumField::new('status', '任务状态');
        $statusField->setEnumCases(TaskStatus::cases());
        yield $statusField
            ->setRequired(true)
            ->formatValue(static function ($value, $entity) {
                if ($value instanceof TaskStatus) {
                    return $value->getLabel();
                }

                return $value;
            })
        ;

        yield TextareaField::new('result', '任务结果')
            ->setMaxLength(65535)
            ->setHelp('任务执行完成后的结果描述')
            ->hideOnIndex()
        ;

        yield BooleanField::new('valid', '有效状态')
            ->renderAsSwitch(false)
            ->setHelp('标记任务是否有效')
        ;

        yield AssociationField::new('executorConversation', '执行者对话')
            ->onlyOnDetail()
            ->setHelp('执行者在任务过程中的对话记录')
        ;

        yield AssociationField::new('managerConversation', '管理者对话')
            ->onlyOnDetail()
            ->setHelp('管理者在任务过程中的对话记录')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->onlyOnDetail()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('name')
            ->add('executor')
            ->add('manager')
            ->add('status')
            ->add(BooleanFilter::new('valid'))
            ->add(DateTimeFilter::new('createTime'))
            ->add(DateTimeFilter::new('updateTime'))
        ;
    }
}
