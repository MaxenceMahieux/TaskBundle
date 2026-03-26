<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\EventSubscriber;

use App\Event\ConfigureMainMenuEvent;
use App\Utils\MenuItemModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $security
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigureMainMenuEvent::class => ['onMenuConfigure', 100],
        ];
    }

    public function onMenuConfigure(ConfigureMainMenuEvent $event): void
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return;
        }

        $menu = $event->getMenu();

        // Add Tasks menu item
        $tasksMenu = new MenuItemModel(
            'tasks',
            'Tasks',
            'task_index',
            [],
            'fas fa-tasks'
        );

        // Add Kanban submenu
        $tasksMenu->addChild(
            new MenuItemModel(
                'task_list',
                'Task List',
                'task_index',
                [],
                'fas fa-list'
            )
        );

        $tasksMenu->addChild(
            new MenuItemModel(
                'task_kanban',
                'Kanban Board',
                'task_kanban',
                [],
                'fas fa-columns'
            )
        );

        if ($this->security->isGranted('create_task')) {
            $tasksMenu->addChild(
                new MenuItemModel(
                    'task_create',
                    'Create Task',
                    'task_create',
                    [],
                    'fas fa-plus'
                )
            );
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $tasksMenu->addChild(
                new MenuItemModel(
                    'task_columns',
                    'Columns',
                    'task_column_index',
                    [],
                    'fas fa-columns'
                )
            );
        }

        $menu->addChild($tasksMenu);
    }
}
