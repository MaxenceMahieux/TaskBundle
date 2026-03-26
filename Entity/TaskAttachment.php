<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kimai2_ext_task_attachments')]
#[ORM\Index(columns: ['task_id'])]
#[ORM\Index(columns: ['uploaded_by_id'])]
class TaskAttachment
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Task::class, inversedBy: 'attachments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Task $task;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $filename;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $storedFilename;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $mimeType;

    #[ORM\Column(type: Types::INTEGER)]
    private int $size;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $uploadedBy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $uploadedAt;

    public function __construct()
    {
        $this->uploadedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function setTask(Task $task): self
    {
        $this->task = $task;
        return $this;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getStoredFilename(): string
    {
        return $this->storedFilename;
    }

    public function setStoredFilename(string $storedFilename): self
    {
        $this->storedFilename = $storedFilename;
        return $this;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function setMimeType(string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function getUploadedBy(): User
    {
        return $this->uploadedBy;
    }

    public function setUploadedBy(User $uploadedBy): self
    {
        $this->uploadedBy = $uploadedBy;
        return $this;
    }

    public function getUploadedAt(): \DateTimeInterface
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(\DateTimeInterface $uploadedAt): self
    {
        $this->uploadedAt = $uploadedAt;
        return $this;
    }
}
