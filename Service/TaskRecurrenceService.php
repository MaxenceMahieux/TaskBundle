<?php
namespace KimaiPlugin\TaskBundle\Service;

use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskRecurrence;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRecurrenceRepository;
use DateTime;
use DateInterval;

class TaskRecurrenceService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private TaskRecurrenceRepository $recurrenceRepository,
        private TaskHistoryService $historyService
    ) {}

    public function processRecurrences(): int
    {
        $now = new DateTime();
        $recurrences = $this->recurrenceRepository->findDueRecurrences($now);
        $createdCount = 0;

        foreach ($recurrences as $recurrence) {
            try {
                $newTask = $this->createTaskFromRecurrence($recurrence);
                $nextDate = $this->calculateNextRunDate($recurrence);
                $recurrence->setNextRunDate($nextDate);
                $this->recurrenceRepository->save($recurrence, true);
                $createdCount++;
            } catch (\Exception $e) {
                // Log error but continue processing other recurrences
                continue;
            }
        }

        return $createdCount;
    }

    public function createTaskFromRecurrence(TaskRecurrence $recurrence): Task
    {
        $originalTask = $recurrence->getTask();

        $newTask = new Task();
        $newTask->setTitle($originalTask->getTitle());
        $newTask->setDescription($originalTask->getDescription());
        $newTask->setStatus($originalTask->getStatus());
        $newTask->setPriority($originalTask->getPriority());
        $newTask->setAssignee($originalTask->getAssignee());
        $newTask->setProject($originalTask->getProject());
        $newTask->setCategory($originalTask->getCategory());
        $newTask->setTags($originalTask->getTags());
        $newTask->setRecurrence($recurrence);
        $newTask->setCreatedAt(new DateTime());

        // Calculate due date based on recurrence pattern
        if ($originalTask->getDueDate()) {
            $dueDate = $this->calculateDueDateFromRecurrence($originalTask->getDueDate(), $recurrence);
            $newTask->setDueDate($dueDate);
        }

        $this->taskRepository->save($newTask, true);

        // Log creation if creator is available
        if ($originalTask->getCreatedBy()) {
            $this->historyService->logCreated($newTask, $originalTask->getCreatedBy());
        }

        return $newTask;
    }

    public function calculateNextRunDate(TaskRecurrence $recurrence): \DateTimeInterface
    {
        $lastRunDate = $recurrence->getNextRunDate() ?? $recurrence->getStartDate() ?? new DateTime();
        $frequency = $recurrence->getFrequency();

        return match ($frequency) {
            'daily' => $lastRunDate->modify('+1 day'),
            'weekly' => $lastRunDate->modify('+1 week'),
            'biweekly' => $lastRunDate->modify('+2 weeks'),
            'monthly' => $lastRunDate->modify('+1 month'),
            'quarterly' => $lastRunDate->modify('+3 months'),
            'yearly' => $lastRunDate->modify('+1 year'),
            default => $lastRunDate->modify('+1 day'),
        };
    }

    private function calculateDueDateFromRecurrence(DateTime $originalDueDate, TaskRecurrence $recurrence): DateTime
    {
        $dueDate = clone $originalDueDate;
        $frequency = $recurrence->getFrequency();

        return match ($frequency) {
            'daily' => $dueDate->modify('+1 day'),
            'weekly' => $dueDate->modify('+1 week'),
            'biweekly' => $dueDate->modify('+2 weeks'),
            'monthly' => $dueDate->modify('+1 month'),
            'quarterly' => $dueDate->modify('+3 months'),
            'yearly' => $dueDate->modify('+1 year'),
            default => $dueDate->modify('+1 day'),
        };
    }
}
