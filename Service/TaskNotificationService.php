<?php
namespace KimaiPlugin\TaskBundle\Service;

use App\Entity\User;
use App\Mail\KimaiMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use KimaiPlugin\TaskBundle\Entity\Task;
use DateTime;

class TaskNotificationService
{
    public function __construct(
        private KimaiMailer $mailer,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function notifyAssigned(Task $task): void
    {
        $assignee = $task->getAssignee();
        if (!$assignee || !$assignee->getEmail()) {
            return;
        }

        $taskUrl = $this->urlGenerator->generate(
            'task_show',
            ['id' => $task->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->to($assignee->getEmail())
            ->subject('Task Assigned: ' . $task->getTitle())
            ->html($this->renderAssignedNotification($task, $taskUrl));

        $this->mailer->send($email);
    }

    public function notifyDueSoon(Task $task): void
    {
        $assignee = $task->getAssignee();
        if (!$assignee || !$assignee->getEmail()) {
            return;
        }

        $taskUrl = $this->urlGenerator->generate(
            'task_show',
            ['id' => $task->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->to($assignee->getEmail())
            ->subject('Task Due Soon: ' . $task->getTitle())
            ->html($this->renderDueSoonNotification($task, $taskUrl));

        $this->mailer->send($email);
    }

    public function notifyOverdue(Task $task): void
    {
        $assignee = $task->getAssignee();
        if (!$assignee || !$assignee->getEmail()) {
            return;
        }

        $taskUrl = $this->urlGenerator->generate(
            'task_show',
            ['id' => $task->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->to($assignee->getEmail())
            ->subject('Task Overdue: ' . $task->getTitle())
            ->html($this->renderOverdueNotification($task, $taskUrl));

        $this->mailer->send($email);
    }

    public function notifyCommentAdded(Task $task, User $commenter): void
    {
        $assignee = $task->getAssignee();
        if (!$assignee || !$assignee->getEmail() || $assignee->getId() === $commenter->getId()) {
            return;
        }

        $taskUrl = $this->urlGenerator->generate(
            'task_show',
            ['id' => $task->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new Email())
            ->to($assignee->getEmail())
            ->subject('New Comment on Task: ' . $task->getTitle())
            ->html($this->renderCommentNotification($task, $commenter, $taskUrl));

        $this->mailer->send($email);
    }

    private function renderAssignedNotification(Task $task, string $taskUrl): string
    {
        return sprintf(
            '<h2>Task Assigned</h2>' .
            '<p>You have been assigned to the following task:</p>' .
            '<p><strong>%s</strong></p>' .
            '<p>Description: %s</p>' .
            '<p><a href="%s">View Task</a></p>',
            htmlspecialchars($task->getTitle()),
            htmlspecialchars($task->getDescription() ?? 'No description'),
            htmlspecialchars($taskUrl)
        );
    }

    private function renderDueSoonNotification(Task $task, string $taskUrl): string
    {
        $dueDate = $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i') : 'Not specified';

        return sprintf(
            '<h2>Task Due Soon</h2>' .
            '<p>The following task is due soon:</p>' .
            '<p><strong>%s</strong></p>' .
            '<p>Due: %s</p>' .
            '<p><a href="%s">View Task</a></p>',
            htmlspecialchars($task->getTitle()),
            htmlspecialchars($dueDate),
            htmlspecialchars($taskUrl)
        );
    }

    private function renderOverdueNotification(Task $task, string $taskUrl): string
    {
        $dueDate = $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i') : 'Not specified';

        return sprintf(
            '<h2>Task Overdue</h2>' .
            '<p>The following task is overdue:</p>' .
            '<p><strong>%s</strong></p>' .
            '<p>Due: %s</p>' .
            '<p><a href="%s">View Task</a></p>',
            htmlspecialchars($task->getTitle()),
            htmlspecialchars($dueDate),
            htmlspecialchars($taskUrl)
        );
    }

    private function renderCommentNotification(Task $task, User $commenter, string $taskUrl): string
    {
        return sprintf(
            '<h2>New Comment</h2>' .
            '<p>%s has commented on a task assigned to you:</p>' .
            '<p><strong>%s</strong></p>' .
            '<p><a href="%s">View Task</a></p>',
            htmlspecialchars($commenter->getDisplayName()),
            htmlspecialchars($task->getTitle()),
            htmlspecialchars($taskUrl)
        );
    }
}
