<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;

#[ORM\Entity(repositoryClass: TaskColumnRepository::class)]
#[ORM\Table(name: 'kimai2_ext_task_columns')]
class TaskColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(length: 7)]
    private string $color = '#6c757d';

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column]
    private bool $isDefault = false;

    #[ORM\Column]
    private bool $isClosedStatus = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
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

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function isClosedStatus(): bool
    {
        return $this->isClosedStatus;
    }

    public function setIsClosedStatus(bool $isClosedStatus): self
    {
        $this->isClosedStatus = $isClosedStatus;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->name;
    }
}
