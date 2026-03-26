<?php
namespace KimaiPlugin\TaskBundle\Service;

use App\Entity\User;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskHistory;
use KimaiPlugin\TaskBundle\Repository\TaskHistoryRepository;
use DateTime;

class TaskHistoryService
{
    public function __construct(
        private TaskHistoryRepository $repository
    ) {}

    public function logCreated(Task $task, User $user): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('created');
        $history->setField('status');
        $history->setOldValue(null);
        $history->setNewValue($task->getStatus());
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logUpdated(Task $task, User $user, string $field, ?string $oldValue, ?string $newValue): void
    {
        if ($oldValue === $newValue) {
            return;
        }

        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('updated');
        $history->setField($field);
        $history->setOldValue($oldValue);
        $history->setNewValue($newValue);
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logStatusChanged(Task $task, User $user, string $oldStatus, string $newStatus): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('status_changed');
        $history->setField('status');
        $history->setOldValue($oldStatus);
        $history->setNewValue($newStatus);
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logAssigned(Task $task, User $user, ?User $oldAssignee, ?User $newAssignee): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('assigned');
        $history->setField('assignee');
        $history->setOldValue($oldAssignee ? $oldAssignee->getDisplayName() : null);
        $history->setNewValue($newAssignee ? $newAssignee->getDisplayName() : null);
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logCommentAdded(Task $task, User $user): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('comment_added');
        $history->setField('comment');
        $history->setOldValue(null);
        $history->setNewValue('Comment added');
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logAttachmentAdded(Task $task, User $user, string $filename): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('attachment_added');
        $history->setField('attachment');
        $history->setOldValue(null);
        $history->setNewValue($filename);
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }

    public function logSubtaskCompleted(Task $task, User $user, string $subtaskTitle): void
    {
        $history = new TaskHistory();
        $history->setTask($task);
        $history->setUser($user);
        $history->setAction('subtask_completed');
        $history->setField('subtask');
        $history->setOldValue(null);
        $history->setNewValue($subtaskTitle);
        $history->setCreatedAt(new DateTime());

        $this->repository->save($history, true);
    }
}
