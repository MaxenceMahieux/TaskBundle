<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case REVIEW = 'review';
    case DONE = 'done';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::REVIEW => 'Review',
            self::DONE => 'Done',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::TODO => '#6c757d',
            self::IN_PROGRESS => '#0d6efd',
            self::REVIEW => '#ffc107',
            self::DONE => '#198754',
            self::CANCELLED => '#dc3545',
        };
    }

    public static function getKanbanStatuses(): array
    {
        return [
            self::TODO,
            self::IN_PROGRESS,
            self::REVIEW,
            self::DONE,
        ];
    }
}
