<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\EventSubscriber;

use App\Entity\TimesheetMeta;
use App\Event\TimesheetMetaDefinitionEvent;
use App\Event\TimesheetMetaDisplayEvent;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class TimesheetSubscriber implements EventSubscriberInterface
{
    public const META_FIELD_TASK = 'task_id';

    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TimesheetMetaDefinitionEvent::class => ['onTimesheetMeta', 200],
            TimesheetMetaDisplayEvent::class => ['onTimesheetDisplay', 200],
        ];
    }

    public function onTimesheetMeta(TimesheetMetaDefinitionEvent $event): void
    {
        $timesheet = $event->getEntity();

        // Get all tasks for the dropdown
        $tasks = $this->taskRepository->findBy([], ['title' => 'ASC']);
        $choices = ['' => null];
        foreach ($tasks as $task) {
            $label = $task->getTitle() . ' (' . $task->getProject()->getName() . ')';
            $choices[$label] = $task->getId();
        }

        $meta = new TimesheetMeta();
        $meta->setName(self::META_FIELD_TASK);
        $meta->setLabel('Task');
        $meta->setIsVisible(true);
        $meta->setIsRequired(false);
        $meta->setType(ChoiceType::class);
        $meta->setOptions([
            'choices' => $choices,
            'required' => false,
            'placeholder' => 'Select a task (optional)',
        ]);

        $timesheet->setMetaField($meta);
    }

    public function onTimesheetDisplay(TimesheetMetaDisplayEvent $event): void
    {
        $event->addField($this->createMetaField());
    }

    private function createMetaField(): TimesheetMeta
    {
        $meta = new TimesheetMeta();
        $meta->setName(self::META_FIELD_TASK);
        $meta->setLabel('Task');
        $meta->setIsVisible(true);

        return $meta;
    }
}
