<?php

namespace Tourze\OpenAITaskBundle\Tests\Repository;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\OpenAITaskBundle\Entity\Task;
use Tourze\OpenAITaskBundle\Repository\TaskRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * TaskRepository 集成测试
 *
 * @internal
 */
#[CoversClass(TaskRepository::class)]
#[RunTestsInSeparateProcesses]
final class TaskRepositoryTest extends AbstractRepositoryTestCase
{
    private TaskRepository $repository;

    public function testSave(): void
    {
        $task = $this->createTestTask();
        $task->setName('Save Test Task');

        $this->getRepository()->save($task, true);

        $foundTask = $this->getRepository()->findOneBy(['name' => 'Save Test Task']);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertSame('Save Test Task', $foundTask->getName());
    }

    public function testRemove(): void
    {
        $task = $this->createTestTask();
        $task->setName('Remove Test Task');
        $this->getRepository()->save($task, true);

        $id = $task->getId();
        $this->getRepository()->remove($task, true);

        $foundTask = $this->getRepository()->find($id);
        $this->assertNull($foundTask);
    }

    public function testFindByWithNullValues(): void
    {
        $task = $this->createTestTask();
        $task->setResult(null);
        $this->getRepository()->save($task, true);

        $results = $this->getRepository()->findBy(['result' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithNullValues(): void
    {
        $task = $this->createTestTask();
        $task->setResult(null);
        $this->getRepository()->save($task, true);

        $count = $this->getRepository()->count(['result' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithAssociations(): void
    {
        $executor = $this->createTestCharacter('Test Executor');
        $task = $this->createTestTask();
        $task->setExecutor($executor);
        $this->getRepository()->save($task, true);

        $results = $this->getRepository()->findBy(['executor' => $executor]);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithAssociations(): void
    {
        $executor = $this->createTestCharacter('Test Executor Count');
        $task = $this->createTestTask();
        $task->setExecutor($executor);
        $this->getRepository()->save($task, true);

        $count = $this->getRepository()->count(['executor' => $executor]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindOneByWithOrderBy(): void
    {
        $task1 = $this->createTestTask();
        $task1->setName('Z Task');
        $this->getRepository()->save($task1, false);

        $task2 = $this->createTestTask();
        $task2->setName('A Task');
        $this->getRepository()->save($task2, false);

        self::getEntityManager()->flush();

        $foundTask = $this->getRepository()->findOneBy([], ['name' => 'ASC']);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertSame('A Task', $foundTask->getName());
    }

    public function testFindByWithManagerAssociation(): void
    {
        $manager = $this->createTestCharacter('Test Manager Association');
        $task = $this->createTestTask();
        $task->setManager($manager);
        $this->getRepository()->save($task, true);

        $results = $this->getRepository()->findBy(['manager' => $manager]);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithManagerAssociation(): void
    {
        $manager = $this->createTestCharacter('Test Manager Count');
        $task = $this->createTestTask();
        $task->setManager($manager);
        $this->getRepository()->save($task, true);

        $count = $this->getRepository()->count(['manager' => $manager]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithExecutorConversationAssociation(): void
    {
        $task = $this->createTestTask();
        $task->setExecutorConversation(null);
        $this->getRepository()->save($task, true);

        $results = $this->getRepository()->findBy(['executorConversation' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithExecutorConversationAssociation(): void
    {
        $task = $this->createTestTask();
        $task->setExecutorConversation(null);
        $this->getRepository()->save($task, true);

        $count = $this->getRepository()->count(['executorConversation' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testFindByWithManagerConversationNull(): void
    {
        $task = $this->createTestTask();
        $task->setManagerConversation(null);
        $this->getRepository()->save($task, true);

        $results = $this->getRepository()->findBy(['managerConversation' => null]);
        $this->assertGreaterThanOrEqual(1, count($results));
    }

    public function testCountWithManagerConversationNull(): void
    {
        $task = $this->createTestTask();
        $task->setManagerConversation(null);
        $this->getRepository()->save($task, true);

        $count = $this->getRepository()->count(['managerConversation' => null]);
        $this->assertGreaterThanOrEqual(1, $count);
    }

    public function testCountByAssociationExecutorShouldReturnCorrectNumber(): void
    {
        $executor = $this->createTestCharacter('Test Executor for Count');
        $otherExecutor = $this->createTestCharacter('Other Executor');
        $manager = $this->createTestCharacter('Test Manager');

        for ($i = 0; $i < 4; ++$i) {
            $task = new Task();
            $task->setName("Task for Executor {$i}");
            $task->setRequirements('Test requirements');
            $task->setExecutor($executor);
            $task->setManager($manager);
            $task->setStatus(TaskStatus::PENDING);
            $task->setValid(true);
            $this->getRepository()->save($task, false);
        }

        for ($i = 0; $i < 2; ++$i) {
            $task = new Task();
            $task->setName("Task for Other Executor {$i}");
            $task->setRequirements('Test requirements');
            $task->setExecutor($otherExecutor);
            $task->setManager($manager);
            $task->setStatus(TaskStatus::PENDING);
            $task->setValid(true);
            $this->getRepository()->save($task, false);
        }

        self::getEntityManager()->flush();

        $count = $this->getRepository()->count(['executor' => $executor]);
        $this->assertSame(4, $count);
    }

    public function testCountByAssociationManagerShouldReturnCorrectNumber(): void
    {
        $executor = $this->createTestCharacter('Test Executor');
        $manager = $this->createTestCharacter('Test Manager for Count');
        $otherManager = $this->createTestCharacter('Other Manager');

        for ($i = 0; $i < 3; ++$i) {
            $task = new Task();
            $task->setName("Task for Manager {$i}");
            $task->setRequirements('Test requirements');
            $task->setExecutor($executor);
            $task->setManager($manager);
            $task->setStatus(TaskStatus::PENDING);
            $task->setValid(true);
            $this->getRepository()->save($task, false);
        }

        $task = new Task();
        $task->setName('Task for Other Manager');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($otherManager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);
        $this->getRepository()->save($task, false);

        self::getEntityManager()->flush();

        $count = $this->getRepository()->count(['manager' => $manager]);
        $this->assertSame(3, $count);
    }

    public function testFindOneByAssociationExecutorShouldReturnMatchingEntity(): void
    {
        $executor = $this->createTestCharacter('Unique Executor for Association');
        $manager = $this->createTestCharacter('Test Manager');

        $task = new Task();
        $task->setName('Task for Unique Executor Association');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($manager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);
        $this->getRepository()->save($task, true);

        $foundTask = $this->getRepository()->findOneBy(['executor' => $executor]);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertSame($executor->getId(), $foundTask->getExecutor()->getId());
    }

    public function testFindOneByAssociationManagerShouldReturnMatchingEntity(): void
    {
        $executor = $this->createTestCharacter('Test Executor');
        $manager = $this->createTestCharacter('Unique Manager for Association');

        $task = new Task();
        $task->setName('Task for Unique Manager Association');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($manager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);
        $this->getRepository()->save($task, true);

        $foundTask = $this->getRepository()->findOneBy(['manager' => $manager]);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertSame($manager->getId(), $foundTask->getManager()->getId());
    }

    public function testFindOneByAssociationExecutorConversationShouldReturnMatchingEntity(): void
    {
        $executor = $this->createTestCharacter('Test Executor');
        $manager = $this->createTestCharacter('Test Manager');
        $conversation = $this->createTestConversation('Unique Executor Conversation');

        $task = new Task();
        $task->setName('Task for Unique Executor Conversation');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($manager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);
        $task->setExecutorConversation($conversation);
        $this->getRepository()->save($task, true);

        $foundTask = $this->getRepository()->findOneBy(['executorConversation' => $conversation]);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertNotNull($foundTask->getExecutorConversation());
        $this->assertSame($conversation->getId(), $foundTask->getExecutorConversation()->getId());
    }

    public function testFindOneByAssociationManagerConversationShouldReturnMatchingEntity(): void
    {
        $executor = $this->createTestCharacter('Test Executor');
        $manager = $this->createTestCharacter('Test Manager');
        $conversation = $this->createTestConversation('Unique Manager Conversation');

        $task = new Task();
        $task->setName('Task for Unique Manager Conversation');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($manager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);
        $task->setManagerConversation($conversation);
        $this->getRepository()->save($task, true);

        $foundTask = $this->getRepository()->findOneBy(['managerConversation' => $conversation]);
        $this->assertInstanceOf(Task::class, $foundTask);
        $this->assertNotNull($foundTask->getManagerConversation());
        $this->assertSame($conversation->getId(), $foundTask->getManagerConversation()->getId());
    }

    /**
     * @return Task
     */
    protected function createNewEntity(): object
    {
        $entity = new Task();
        $entity->setName('Test Task ' . uniqid());
        $entity->setRequirements('Test requirements');
        $entity->setStatus(TaskStatus::PENDING);
        $entity->setValid(true);

        // 创建必需的关联实体并持久化它们
        $executor = new Character();
        $executor->setName('Test Executor ' . uniqid());
        $executor->setSystemPrompt('Test prompt');

        $manager = new Character();
        $manager->setName('Test Manager ' . uniqid());
        $manager->setSystemPrompt('Test prompt');

        // 持久化关联实体
        self::getEntityManager()->persist($executor);
        self::getEntityManager()->persist($manager);

        $entity->setExecutor($executor);
        $entity->setManager($manager);

        return $entity;
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(TaskRepository::class);
    }

    protected function getRepository(): TaskRepository
    {
        return $this->repository;
    }

    private function createTestTask(): Task
    {
        $executor = $this->createTestCharacter('Default Executor');
        $manager = $this->createTestCharacter('Default Manager');

        $task = new Task();
        $task->setName('Test Task');
        $task->setRequirements('Test requirements');
        $task->setExecutor($executor);
        $task->setManager($manager);
        $task->setStatus(TaskStatus::PENDING);
        $task->setValid(true);

        return $task;
    }

    private function createTestCharacter(string $name): Character
    {
        $character = new Character();
        $character->setName($name);
        $character->setSystemPrompt('Test prompt');

        self::getEntityManager()->persist($character);
        self::getEntityManager()->flush();

        return $character;
    }

    private function createTestConversation(string $title): Conversation
    {
        $actor = $this->createTestCharacter('Test Actor for Conversation');

        $conversation = new Conversation();
        $conversation->setTitle($title);
        $conversation->setActor($actor);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        return $conversation;
    }
}
