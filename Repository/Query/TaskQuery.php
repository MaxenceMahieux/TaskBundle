<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Repository\Query;

use App\Entity\Project;
use App\Entity\User;
use App\Repository\Query\BaseQuery;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Entity\TaskPriority;

class TaskQuery extends BaseQuery
{
    private ?string $textSearch = null;
    private ?Project $project = null;
    private ?User $assignee = null;
    private ?TaskColumn $column = null;
    private ?TaskPriority $priority = null;
    private ?\DateTimeInterface $dueDateFrom = null;
    private ?\DateTimeInterface $dueDateTo = null;
    private ?bool $isOverdue = null;
    private ?bool $myTasksOnly = null;

    public function __construct()
    {
        $this->setDefaults([
            'textSearch' => null,
            'project' => null,
            'assignee' => null,
            'column' => null,
            'priority' => null,
            'dueDateFrom' => null,
            'dueDateTo' => null,
            'isOverdue' => false,
            'myTasksOnly' => false,
        ]);
    }

    public function getTextSearch(): ?string
    {
        return $this->textSearch;
    }

    public function setTextSearch(?string $textSearch): self
    {
        $this->textSearch = $textSearch;
        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
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

    public function getColumn(): ?TaskColumn
    {
        return $this->column;
    }

    public function setColumn(?TaskColumn $column): self
    {
        $this->column = $column;
        return $this;
    }

    public function getPriority(): ?TaskPriority
    {
        return $this->priority;
    }

    public function setPriority(?TaskPriority $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getDueDateFrom(): ?\DateTimeInterface
    {
        return $this->dueDateFrom;
    }

    public function setDueDateFrom(?\DateTimeInterface $dueDateFrom): self
    {
        $this->dueDateFrom = $dueDateFrom;
        return $this;
    }

    public function getDueDateTo(): ?\DateTimeInterface
    {
        return $this->dueDateTo;
    }

    public function setDueDateTo(?\DateTimeInterface $dueDateTo): self
    {
        $this->dueDateTo = $dueDateTo;
        return $this;
    }

    public function isOverdue(): bool
    {
        return $this->isOverdue ?? false;
    }

    public function setIsOverdue(?bool $isOverdue): self
    {
        $this->isOverdue = $isOverdue;
        return $this;
    }

    public function isMyTasksOnly(): bool
    {
        return $this->myTasksOnly ?? false;
    }

    public function setMyTasksOnly(?bool $myTasksOnly): self
    {
        $this->myTasksOnly = $myTasksOnly;
        return $this;
    }

    public function hasFilter(): bool
    {
        return $this->textSearch !== null
            || $this->project !== null
            || $this->assignee !== null
            || $this->column !== null
            || $this->priority !== null
            || $this->dueDateFrom !== null
            || $this->dueDateTo !== null
            || ($this->isOverdue ?? false)
            || ($this->myTasksOnly ?? false);
    }

    public function resetFilters(): void
    {
        $this->textSearch = null;
        $this->project = null;
        $this->assignee = null;
        $this->column = null;
        $this->priority = null;
        $this->dueDateFrom = null;
        $this->dueDateTo = null;
        $this->isOverdue = false;
        $this->myTasksOnly = false;
    }
}
