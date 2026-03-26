<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Service\TaskTimesheetService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskTimerController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskTimesheetService $timesheetService
    ) {
    }

    #[Route(path: '/{id}/start-timer', name: 'task_start_timer', methods: ['POST'])]
    public function startTimer(Task $task): Response
    {
        $user = $this->getUser();

        // Check if there's already an active timer for this task
        $activeTimesheet = $this->timesheetService->getActiveTimesheet($task, $user);

        if ($activeTimesheet !== null) {
            $this->flashWarning('Timer already running for this task');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        try {
            $this->timesheetService->startTimer($task, $user);
            $this->flashSuccess('Timer started');
        } catch (\Exception $e) {
            $this->flashError('Could not start timer: ' . $e->getMessage());
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }

    #[Route(path: '/{id}/stop-timer', name: 'task_stop_timer', methods: ['POST'])]
    public function stopTimer(Task $task): Response
    {
        $user = $this->getUser();

        $activeTimesheet = $this->timesheetService->getActiveTimesheet($task, $user);

        if ($activeTimesheet === null) {
            $this->flashWarning('No active timer for this task');
            return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
        }

        try {
            $this->timesheetService->stopTimer($activeTimesheet);
            $this->flashSuccess('Timer stopped');
        } catch (\Exception $e) {
            $this->flashError('Could not stop timer: ' . $e->getMessage());
        }

        return $this->redirectToRoute('task_show', ['id' => $task->getId()]);
    }
}
