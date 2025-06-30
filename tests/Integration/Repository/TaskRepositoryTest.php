<?php

namespace Tourze\OpenAITaskBundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Tourze\OpenAITaskBundle\Entity\Task;
use Tourze\OpenAITaskBundle\Repository\TaskRepository;

class TaskRepositoryTest extends TestCase
{
    private ?EntityManagerInterface $entityManager = null;
    private ?TaskRepository $repository = null;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $managerRegistry = $this->createMock(\Doctrine\Persistence\ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($this->entityManager);
        
        $this->repository = new TaskRepository($managerRegistry);
    }

    public function testRepositoryCanBeInstantiated(): void
    {
        $this->assertInstanceOf(TaskRepository::class, $this->repository);
    }
}