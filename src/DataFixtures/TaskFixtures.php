<?php

namespace Tourze\OpenAITaskBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Enum\TaskStatus;
use Tourze\OpenAITaskBundle\Entity\Task;

class TaskFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $executor = new Character();
        $executor->setName('AI助手');
        $executor->setDescription('专业的AI任务执行助手');
        $executor->setSystemPrompt('你是一个专业的AI任务执行助手，擅长代码分析、文档生成和测试编写。请严格按照要求完成任务，确保输出高质量的结果。');
        $manager->persist($executor);

        $managerCharacter = new Character();
        $managerCharacter->setName('项目经理');
        $managerCharacter->setDescription('负责任务管理和协调');
        $managerCharacter->setSystemPrompt('你是一个经验丰富的项目经理，负责任务管理和协调。请根据执行者的输出评估任务进度，并给出明确的指导意见。');
        $manager->persist($managerCharacter);

        $task1 = new Task();
        $task1->setName('代码审查任务');
        $task1->setRequirements('审查提交的PHP代码，确保符合编码规范和最佳实践');
        $task1->setExecutor($executor);
        $task1->setManager($managerCharacter);
        $task1->setStatus(TaskStatus::PENDING);
        $task1->setValid(true);
        $manager->persist($task1);

        $task2 = new Task();
        $task2->setName('API文档生成');
        $task2->setRequirements('根据控制器代码自动生成RESTful API文档');
        $task2->setExecutor($executor);
        $task2->setManager($managerCharacter);
        $task2->setStatus(TaskStatus::RUNNING);
        $task2->setResult('正在分析控制器代码结构...');
        $task2->setValid(true);
        $manager->persist($task2);

        $task3 = new Task();
        $task3->setName('单元测试生成');
        $task3->setRequirements('为指定的类生成完整的PHPUnit单元测试');
        $task3->setExecutor($executor);
        $task3->setManager($managerCharacter);
        $task3->setStatus(TaskStatus::COMPLETED);
        $task3->setResult('已成功生成覆盖率达95%的单元测试');
        $task3->setValid(true);
        $manager->persist($task3);

        $manager->flush();
    }
}
