<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Service;

use App\Entity\Timesheet;
use App\Entity\User;
use App\Repository\TimesheetRepository;
use App\Timesheet\TimesheetService;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\EventSubscriber\TimesheetSubscriber;

class TaskTimesheetService
{
    public function __construct(
        private readonly TimesheetRepository $timesheetRepository,
        private readonly TimesheetService $timesheetService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Get total duration in seconds for a task
     */
    public function getTotalDuration(Task $task): int
    {
        $taskId = (string) $task->getId();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('SUM(t.duration)')
            ->from(Timesheet::class, 't')
            ->join('t.meta', 'm')
            ->where('m.name = :metaName')
            ->andWhere('m.value = :taskId')
            ->andWhere('t.duration IS NOT NULL')
            ->setParameter('metaName', TimesheetSubscriber::META_FIELD_TASK)
            ->setParameter('taskId', $taskId);

        $result = $qb->getQuery()->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    /**
     * Get all timesheets for a task
     * @return Timesheet[]
     */
    public function getTimesheets(Task $task): array
    {
        $taskId = (string) $task->getId();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->join('t.meta', 'm')
            ->where('m.name = :metaName')
            ->andWhere('m.value = :taskId')
            ->orderBy('t.begin', 'DESC')
            ->setParameter('metaName', TimesheetSubscriber::META_FIELD_TASK)
            ->setParameter('taskId', $taskId);

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if there's an active timer for a task
     */
    public function getActiveTimesheet(Task $task, User $user): ?Timesheet
    {
        $taskId = (string) $task->getId();

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('t')
            ->from(Timesheet::class, 't')
            ->join('t.meta', 'm')
            ->where('m.name = :metaName')
            ->andWhere('m.value = :taskId')
            ->andWhere('t.user = :user')
            ->andWhere('t.end IS NULL')
            ->setParameter('metaName', TimesheetSubscriber::META_FIELD_TASK)
            ->setParameter('taskId', $taskId)
            ->setParameter('user', $user)
            ->setMaxResults(1);

        $results = $qb->getQuery()->getResult();

        return $results[0] ?? null;
    }

    /**
     * Start a new timer for a task
     */
    public function startTimer(Task $task, User $user): Timesheet
    {
        $timesheet = new Timesheet();
        $timesheet->setUser($user);
        $timesheet->setProject($task->getProject());
        $timesheet->setBegin(new \DateTime());
        $timesheet->setDescription('Task: ' . $task->getTitle());

        // Get the first activity of the project, or create without activity
        $activities = $task->getProject()->getActivities();
        if ($activities->count() > 0) {
            $timesheet->setActivity($activities->first());
        }

        // Save to get ID, then add meta field
        $this->timesheetService->prepareNewTimesheet($timesheet);
        $this->entityManager->persist($timesheet);
        $this->entityManager->flush();

        // Add task meta field
        $meta = $timesheet->getMetaField(TimesheetSubscriber::META_FIELD_TASK);
        if ($meta !== null) {
            $meta->setValue((string) $task->getId());
        }

        $this->entityManager->flush();

        return $timesheet;
    }

    /**
     * Stop an active timer
     */
    public function stopTimer(Timesheet $timesheet): void
    {
        $this->timesheetService->stopTimesheet($timesheet);
    }

    /**
     * Format duration in human readable format
     */
    public static function formatDuration(int $seconds): string
    {
        if ($seconds === 0) {
            return '0h';
        }

        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($hours > 0 && $minutes > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        } elseif ($hours > 0) {
            return sprintf('%dh', $hours);
        } else {
            return sprintf('%dm', $minutes);
        }
    }
}
