<?php

namespace Tourze\OpenAITaskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\TaskStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;

#[ORM\Entity]
#[ORM\Table(name: 'ims_open_ai_task', options: ['comment' => 'AI任务'])]
class Task implements \Stringable
{
    use TimestampableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '任务名称'])]
    private string $name;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '任务要求'])]
    private string $requirements;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'executor_id', nullable: false, onDelete: 'CASCADE')]
    private Character $executor;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'manager_id', nullable: false, onDelete: 'CASCADE')]
    private Character $manager;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TaskStatus::class, options: ['comment' => '任务状态'])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '任务结果'])]
    private ?string $result = null;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'executor_conversation_id', nullable: true, onDelete: 'SET NULL')]
    private ?Conversation $executorConversation = null;

    #[Ignore]
    #[ORM\OneToOne(targetEntity: Conversation::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'manager_conversation_id', nullable: true, onDelete: 'SET NULL')]
    private ?Conversation $managerConversation = null;

    public function __toString(): string
    {
        $id = $this->getId();
        if ($id === null || $id === '') {
            return '';
        }

        return $this->getName();
    }


    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getRequirements(): string
    {
        return $this->requirements;
    }

    public function setRequirements(string $requirements): self
    {
        $this->requirements = $requirements;

        return $this;
    }

    public function getExecutor(): Character
    {
        return $this->executor;
    }

    public function setExecutor(Character $executor): self
    {
        $this->executor = $executor;

        return $this;
    }

    public function getManager(): Character
    {
        return $this->manager;
    }

    public function setManager(Character $manager): self
    {
        $this->manager = $manager;

        return $this;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): self
    {
        $this->result = $result;

        return $this;
    }

    public function getExecutorConversation(): ?Conversation
    {
        return $this->executorConversation;
    }

    public function setExecutorConversation(?Conversation $conversation): self
    {
        $this->executorConversation = $conversation;

        return $this;
    }

    public function getManagerConversation(): ?Conversation
    {
        return $this->managerConversation;
    }

    public function setManagerConversation(?Conversation $conversation): self
    {
        $this->managerConversation = $conversation;

        return $this;
    }}
