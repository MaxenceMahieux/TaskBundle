<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Widget;

use App\Widget\Type\AbstractWidget;
use App\Widget\WidgetInterface;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;

final class TasksByColumn extends AbstractWidget
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskColumnRepository $columnRepository
    ) {
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'task_kanban',
            'icon' => 'columns',
        ], parent::getOptions($options));
    }

    public function getWidth(): int
    {
        return WidgetInterface::WIDTH_LARGE;
    }

    public function getHeight(): int
    {
        return WidgetInterface::HEIGHT_MEDIUM;
    }

    public function getData(array $options = []): mixed
    {
        $columns = $this->columnRepository->findAllOrdered();
        $data = [];

        foreach ($columns as $column) {
            $count = count($this->taskRepository->findByColumn($column));
            $data[] = [
                'name' => $column->getName(),
                'color' => $column->getColor(),
                'count' => $count,
            ];
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Tasks by Status';
    }

    public function getPermissions(): array
    {
        return ['IS_AUTHENTICATED_REMEMBERED'];
    }

    public function getTemplateName(): string
    {
        return '@Task/widget/tasks-by-column.html.twig';
    }

    public function getId(): string
    {
        return 'TasksByColumn';
    }
}
