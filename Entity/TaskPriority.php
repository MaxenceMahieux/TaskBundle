<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

enum TaskPriority: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case URGENT = 'urgent';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MEDIUM => 'Medium',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::LOW => '#6c757d',
            self::MEDIUM => '#0d6efd',
            self::HIGH => '#fd7e14',
            self::URGENT => '#dc3545',
        };
    }

    public function getOrder(): int
    {
        return match ($this) {
            self::LOW => 1,
            self::MEDIUM => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }
}
