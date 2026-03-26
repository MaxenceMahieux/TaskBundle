<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Widget;

use App\Widget\Type\AbstractWidget;
use App\Widget\WidgetInterface;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;

final class TotalTasks extends AbstractWidget
{
    public function __construct(private TaskRepository $repository)
    {
    }

    public function getOptions(array $options = []): array
    {
        return array_merge([
            'route' => 'task_index',
            'icon' => 'activity',
            'color' => WidgetInterface::COLOR_TOTAL,
        ], parent::getOptions($options));
    }

    public function getData(array $options = []): mixed
    {
        return $this->repository->count([]);
    }

    public function getTitle(): string
    {
        return 'Total Tasks';
    }

    public function getPermissions(): array
    {
        return ['IS_AUTHENTICATED_REMEMBERED'];
    }

    public function getTemplateName(): string
    {
        return 'widget/widget-more.html.twig';
    }

    public function getId(): string
    {
        return 'TaskTotal';
    }
}
