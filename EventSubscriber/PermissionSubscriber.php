<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\EventSubscriber;

use App\Event\PermissionSectionsEvent;
use App\Event\PermissionsEvent;
use App\Model\PermissionSection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PermissionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PermissionsEvent::class => ['onPermissions', 100],
            PermissionSectionsEvent::class => ['onPermissionSections', 100],
        ];
    }

    public function onPermissionSections(PermissionSectionsEvent $event): void
    {
        $event->addSection(new PermissionSection('task', 'Tasks'));
    }

    public function onPermissions(PermissionsEvent $event): void
    {
        $event->addPermissions('task', [
            'view_task',
            'create_task',
            'edit_task',
            'delete_task',
            'view_all_tasks',
            'edit_all_tasks',
        ]);
    }
}
