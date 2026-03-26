<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kimai2_ext_task_recurrences')]
#[ORM\Index(columns: ['task_id'])]
class TaskRecurrence
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Task::class, inversedBy: 'recurrence')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Task $task;

    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $frequency;

    #[ORM\Column(type: Types::INTEGER)]
    private int $interval = 1;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $daysOfWeek = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $dayOfMonth = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $startDate;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $nextRunDate;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastRunDate = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\Column(type: Types::INTEGER)]
    private int $createdTasks = 0;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->nextRunDate = new \DateTime();
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

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function setFrequency(string $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function setInterval(int $interval): self
    {
        $this->interval = $interval;
        return $this;
    }

    public function getDaysOfWeek(): ?array
    {
        return $this->daysOfWeek;
    }

    public function setDaysOfWeek(?array $daysOfWeek): self
    {
        $this->daysOfWeek = $daysOfWeek;
        return $this;
    }

    public function getDayOfMonth(): ?int
    {
        return $this->dayOfMonth;
    }

    public function setDayOfMonth(?int $dayOfMonth): self
    {
        $this->dayOfMonth = $dayOfMonth;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getNextRunDate(): \DateTimeInterface
    {
        return $this->nextRunDate;
    }

    public function setNextRunDate(\DateTimeInterface $nextRunDate): self
    {
        $this->nextRunDate = $nextRunDate;
        return $this;
    }

    public function getLastRunDate(): ?\DateTimeInterface
    {
        return $this->lastRunDate;
    }

    public function setLastRunDate(?\DateTimeInterface $lastRunDate): self
    {
        $this->lastRunDate = $lastRunDate;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getCreatedTasks(): int
    {
        return $this->createdTasks;
    }

    public function setCreatedTasks(int $createdTasks): self
    {
        $this->createdTasks = $createdTasks;
        return $this;
    }
}
