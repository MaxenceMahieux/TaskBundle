<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'kimai2_ext_tasks')]
#[ORM\Index(columns: ['project_id'])]
#[ORM\Index(columns: ['assignee_id'])]
#[ORM\Index(columns: ['column_id'])]
#[ORM\HasLifecycleCallbacks]
class Task
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $title;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignee = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $createdBy;

    #[ORM\ManyToOne(targetEntity: TaskColumn::class)]
    #[ORM\JoinColumn(nullable: false)]
    private TaskColumn $column;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: TaskPriority::class)]
    private TaskPriority $priority = TaskPriority::MEDIUM;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $estimatedMinutes = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isInternal = false;

    #[ORM\Column(type: Types::INTEGER)]
    private int $position = 0;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'childTasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Task $parentTask = null;

    #[ORM\OneToMany(mappedBy: 'parentTask', targetEntity: Task::class)]
    private Collection $childTasks;

    #[ORM\ManyToMany(targetEntity: Task::class, inversedBy: 'blocksTasks')]
    #[ORM\JoinTable(name: 'kimai2_ext_task_dependencies')]
    #[ORM\JoinColumn(name: 'task_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'blocked_by_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $blockedByTasks;

    #[ORM\ManyToMany(targetEntity: Task::class, mappedBy: 'blockedByTasks')]
    private Collection $blocksTasks;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskComment::class, orphanRemoval: true)]
    private Collection $comments;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskHistory::class, orphanRemoval: true)]
    private Collection $history;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskAttachment::class, orphanRemoval: true)]
    private Collection $attachments;

    #[ORM\OneToMany(mappedBy: 'task', targetEntity: TaskSubtask::class, orphanRemoval: true)]
    private Collection $subtasks;

    #[ORM\OneToOne(mappedBy: 'task', targetEntity: TaskRecurrence::class, orphanRemoval: true)]
    private ?TaskRecurrence $recurrence = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->childTasks = new ArrayCollection();
        $this->blockedByTasks = new ArrayCollection();
        $this->blocksTasks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->history = new ArrayCollection();
        $this->attachments = new ArrayCollection();
        $this->subtasks = new ArrayCollection();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;
        return $this;
    }

    public function getAssignee(): ?User
    {
        return $this->assignee;
    }

    public function setAssignee(?User $assignee): self
    {
        $this->assignee = $assignee;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getColumn(): TaskColumn
    {
        return $this->column;
    }

    public function setColumn(TaskColumn $column): self
    {
        $this->column = $column;
        return $this;
    }

    /**
     * Alias for getColumn() for template compatibility
     */
    public function getStatus(): TaskColumn
    {
        return $this->column;
    }

    public function setStatus(TaskColumn $column): self
    {
        $this->column = $column;
        return $this;
    }

    public function getPriority(): TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(TaskPriority $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function getEstimatedMinutes(): ?int
    {
        return $this->estimatedMinutes;
    }

    public function setEstimatedMinutes(?int $estimatedMinutes): self
    {
        $this->estimatedMinutes = $estimatedMinutes;
        return $this;
    }

    public function isInternal(): bool
    {
        return $this->isInternal;
    }

    public function setIsInternal(bool $isInternal): self
    {
        $this->isInternal = $isInternal;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isOverdue(): bool
    {
        if ($this->dueDate === null) {
            return false;
        }

        if ($this->column->isClosedStatus()) {
            return false;
        }

        return $this->dueDate < new \DateTime('today');
    }

    public function getParentTask(): ?Task
    {
        return $this->parentTask;
    }

    public function setParentTask(?Task $parentTask): self
    {
        $this->parentTask = $parentTask;
        return $this;
    }

    public function getChildTasks(): Collection
    {
        return $this->childTasks;
    }

    public function addChildTask(Task $childTask): self
    {
        if (!$this->childTasks->contains($childTask)) {
            $this->childTasks->add($childTask);
            $childTask->setParentTask($this);
        }
        return $this;
    }

    public function removeChildTask(Task $childTask): self
    {
        if ($this->childTasks->removeElement($childTask)) {
            if ($childTask->getParentTask() === $this) {
                $childTask->setParentTask(null);
            }
        }
        return $this;
    }

    public function getBlockedByTasks(): Collection
    {
        return $this->blockedByTasks;
    }

    public function addBlockedByTask(Task $blockedByTask): self
    {
        if (!$this->blockedByTasks->contains($blockedByTask)) {
            $this->blockedByTasks->add($blockedByTask);
            $blockedByTask->addBlocksTask($this);
        }
        return $this;
    }

    public function removeBlockedByTask(Task $blockedByTask): self
    {
        if ($this->blockedByTasks->removeElement($blockedByTask)) {
            $blockedByTask->removeBlocksTask($this);
        }
        return $this;
    }

    public function getBlocksTasks(): Collection
    {
        return $this->blocksTasks;
    }

    public function addBlocksTask(Task $blocksTask): self
    {
        if (!$this->blocksTasks->contains($blocksTask)) {
            $this->blocksTasks->add($blocksTask);
        }
        return $this;
    }

    public function removeBlocksTask(Task $blocksTask): self
    {
        $this->blocksTasks->removeElement($blocksTask);
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(TaskComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setTask($this);
        }
        return $this;
    }

    public function removeComment(TaskComment $comment): self
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getTask() === $this) {
                $comment->setTask(null);
            }
        }
        return $this;
    }

    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function addHistoryEntry(TaskHistory $historyEntry): self
    {
        if (!$this->history->contains($historyEntry)) {
            $this->history->add($historyEntry);
            $historyEntry->setTask($this);
        }
        return $this;
    }

    public function removeHistoryEntry(TaskHistory $historyEntry): self
    {
        if ($this->history->removeElement($historyEntry)) {
            if ($historyEntry->getTask() === $this) {
                $historyEntry->setTask(null);
            }
        }
        return $this;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(TaskAttachment $attachment): self
    {
        if (!$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setTask($this);
        }
        return $this;
    }

    public function removeAttachment(TaskAttachment $attachment): self
    {
        if ($this->attachments->removeElement($attachment)) {
            if ($attachment->getTask() === $this) {
                $attachment->setTask(null);
            }
        }
        return $this;
    }

    public function getSubtasks(): Collection
    {
        return $this->subtasks;
    }

    public function addSubtask(TaskSubtask $subtask): self
    {
        if (!$this->subtasks->contains($subtask)) {
            $this->subtasks->add($subtask);
            $subtask->setTask($this);
        }
        return $this;
    }

    public function removeSubtask(TaskSubtask $subtask): self
    {
        if ($this->subtasks->removeElement($subtask)) {
            if ($subtask->getTask() === $this) {
                $subtask->setTask(null);
            }
        }
        return $this;
    }

    public function getRecurrence(): ?TaskRecurrence
    {
        return $this->recurrence;
    }

    public function setRecurrence(?TaskRecurrence $recurrence): self
    {
        if ($recurrence !== null && $recurrence->getTask() !== $this) {
            $recurrence->setTask($this);
        }
        $this->recurrence = $recurrence;
        return $this;
    }
}
