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
use Tourze\DoctrineSnowflakeBundle\Service\SnowflakeIdGenerator;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\EasyAdmin\Attribute\Action\Copyable;
use Tourze\EasyAdmin\Attribute\Action\Creatable;
use Tourze\EasyAdmin\Attribute\Action\Deletable;
use Tourze\EasyAdmin\Attribute\Action\Editable;
use Tourze\EasyAdmin\Attribute\Column\BoolColumn;
use Tourze\EasyAdmin\Attribute\Column\CopyColumn;
use Tourze\EasyAdmin\Attribute\Column\ExportColumn;
use Tourze\EasyAdmin\Attribute\Column\ListColumn;
use Tourze\EasyAdmin\Attribute\Field\FormField;
use Tourze\EasyAdmin\Attribute\Filter\Filterable;
use Tourze\EasyAdmin\Attribute\Permission\AsPermission;

#[AsPermission(title: 'AI任务')]
#[Creatable]
#[Editable]
#[Deletable]
#[Copyable]
#[ORM\Entity]
#[ORM\Table(name: 'ims_open_ai_task', options: ['comment' => 'AI任务'])]
class Task implements \Stringable
{
    #[ExportColumn]
    #[ListColumn(order: -1, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'recursive_view', 'api_tree'])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(SnowflakeIdGenerator::class)]
    #[ORM\Column(type: Types::BIGINT, nullable: false, options: ['comment' => 'ID'])]
    private ?string $id = null;

    #[BoolColumn]
    #[IndexColumn]
    #[TrackColumn]
    #[Groups(['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    #[ListColumn(order: 97)]
    #[FormField(order: 97)]
    private ?bool $valid = false;

    #[CopyColumn(suffix: '-复制')]
    #[ListColumn]
    #[FormField]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '任务名称'])]
    private string $name;

    #[CopyColumn]
    #[FormField]
    #[ORM\Column(type: Types::TEXT, options: ['comment' => '任务要求'])]
    private string $requirements;

    #[CopyColumn]
    #[ListColumn(title: '执行者')]
    #[FormField(title: '执行者')]
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'executor_id', nullable: false, onDelete: 'CASCADE')]
    private Character $executor;

    #[CopyColumn]
    #[ListColumn(title: '负责人')]
    #[FormField(title: '负责人')]
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'manager_id', nullable: false, onDelete: 'CASCADE')]
    private Character $manager;

    #[ListColumn]
    #[FormField]
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

    #[Filterable]
    #[IndexColumn]
    #[ListColumn(order: 98, sorter: true)]
    #[ExportColumn]
    #[CreateTimeColumn]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeInterface $createTime = null;

    #[UpdateTimeColumn]
    #[ListColumn(order: 99, sorter: true)]
    #[Groups(['restful_read', 'admin_curd', 'restful_read'])]
    #[Filterable]
    #[ExportColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true, options: ['comment' => '更新时间'])]
    private ?\DateTimeInterface $updateTime = null;

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return $this->getName();
    }

    public function getId(): ?string
    {
        return $this->id;
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
    }

    public function setCreateTime(?\DateTimeInterface $createdAt): void
    {
        $this->createTime = $createdAt;
    }

    public function getCreateTime(): ?\DateTimeInterface
    {
        return $this->createTime;
    }

    public function setUpdateTime(?\DateTimeInterface $updateTime): void
    {
        $this->updateTime = $updateTime;
    }

    public function getUpdateTime(): ?\DateTimeInterface
    {
        return $this->updateTime;
    }
}
