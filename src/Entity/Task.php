<?php

namespace Tourze\OpenAITaskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\TaskStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
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

    #[Assert\Type(type: 'bool', message: '有效性标志必须是布尔值')]
    #[IndexColumn]
    #[TrackColumn]
    #[Groups(groups: ['admin_curd', 'restful_read', 'restful_read', 'restful_write'])]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[Assert\NotBlank(message: '任务名称不能为空')]
    #[Assert\Length(max: 50, maxMessage: '任务名称长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '任务名称'])]
    private string $name;

    #[Assert\NotBlank(message: '任务要求不能为空')]
    #[Assert\Length(max: 65535, maxMessage: '任务要求长度不能超过 {{ limit }} 个字符')]
    #[ORM\Column(type: Types::TEXT, options: ['comment' => '任务要求'])]
    private string $requirements;

    #[Assert\NotNull(message: '执行者不能为空')]
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'executor_id', nullable: false, onDelete: 'CASCADE')]
    private Character $executor;

    #[Assert\NotNull(message: '管理者不能为空')]
    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(name: 'manager_id', nullable: false, onDelete: 'CASCADE')]
    private Character $manager;

    #[Assert\Choice(callback: [TaskStatus::class, 'cases'], message: '无效的任务状态')]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: TaskStatus::class, options: ['comment' => '任务状态'])]
    private TaskStatus $status = TaskStatus::PENDING;

    #[Assert\Length(max: 65535, maxMessage: '任务结果长度不能超过 {{ limit }} 个字符')]
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
        if (null === $id || '' === $id) {
            return '';
        }

        return $this->getName();
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getRequirements(): string
    {
        return $this->requirements;
    }

    public function setRequirements(string $requirements): void
    {
        $this->requirements = $requirements;
    }

    public function getExecutor(): Character
    {
        return $this->executor;
    }

    public function setExecutor(Character $executor): void
    {
        $this->executor = $executor;
    }

    public function getManager(): Character
    {
        return $this->manager;
    }

    public function setManager(Character $manager): void
    {
        $this->manager = $manager;
    }

    public function getStatus(): TaskStatus
    {
        return $this->status;
    }

    public function setStatus(TaskStatus $status): void
    {
        $this->status = $status;
    }

    public function getResult(): ?string
    {
        return $this->result;
    }

    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    public function getExecutorConversation(): ?Conversation
    {
        return $this->executorConversation;
    }

    public function setExecutorConversation(?Conversation $conversation): void
    {
        $this->executorConversation = $conversation;
    }

    public function getManagerConversation(): ?Conversation
    {
        return $this->managerConversation;
    }

    public function setManagerConversation(?Conversation $conversation): void
    {
        $this->managerConversation = $conversation;
    }
}
