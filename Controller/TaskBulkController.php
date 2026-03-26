<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Service\TaskHistoryService;
use KimaiPlugin\TaskBundle\Service\TaskNotificationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/bulk')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskBulkController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskColumnRepository $columnRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TaskHistoryService $historyService,
        private readonly TaskNotificationService $notificationService
    ) {
    }

    private function getJsonData(Request $request): array
    {
        $content = $request->getContent();
        return json_decode($content, true) ?? [];
    }

    #[Route(path: '/move', name: 'task_bulk_move', methods: ['POST'])]
    public function move(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $taskIds = $data['task_ids'] ?? [];
        $columnId = (int) ($data['column_id'] ?? 0);
        $token = $data['_token'] ?? '';

        if (empty($taskIds)) {
            return $this->json(['success' => false, 'error' => 'No tasks provided'], 400);
        }

        if (!$this->isCsrfTokenValid('bulk', $token)) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $column = $this->columnRepository->find($columnId);
        if ($column === null) {
            return $this->json(['success' => false, 'error' => 'Column not found'], 404);
        }

        $currentUser = $this->getUser();
        $moved = 0;

        foreach ($taskIds as $taskId) {
            $task = $this->taskRepository->find((int) $taskId);
            if ($task !== null) {
                $oldColumn = $task->getColumn();
                $task->setColumn($column);
                $task->setPosition($this->taskRepository->getNextPosition($column));
                $this->entityManager->persist($task);
                $this->historyService->logStatusChanged($task, $currentUser, $oldColumn?->getName() ?? '', $column->getName());
                $moved++;
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true, 'moved' => $moved]);
    }

    #[Route(path: '/assign', name: 'task_bulk_assign', methods: ['POST'])]
    public function assign(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $taskIds = $data['task_ids'] ?? [];
        $userId = $data['user_id'] ?? null;
        $token = $data['_token'] ?? '';

        if (empty($taskIds)) {
            return $this->json(['success' => false, 'error' => 'No tasks provided'], 400);
        }

        if (!$this->isCsrfTokenValid('bulk', $token)) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $assignee = null;
        if (!empty($userId)) {
            $assignee = $this->userRepository->find((int) $userId);
            if ($assignee === null) {
                return $this->json(['success' => false, 'error' => 'User not found'], 404);
            }
        }

        $currentUser = $this->getUser();
        $assigned = 0;

        foreach ($taskIds as $taskId) {
            $task = $this->taskRepository->find((int) $taskId);
            if ($task !== null) {
                $oldAssignee = $task->getAssignee();
                $task->setAssignee($assignee);
                $this->entityManager->persist($task);
                $this->historyService->logAssigned($task, $currentUser, $oldAssignee, $assignee);
                if ($assignee !== null) {
                    $this->notificationService->notifyAssigned($task);
                }
                $assigned++;
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true, 'assigned' => $assigned]);
    }

    #[Route(path: '/delete', name: 'task_bulk_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $data = $this->getJsonData($request);
        $taskIds = $data['task_ids'] ?? [];
        $token = $data['_token'] ?? '';

        if (empty($taskIds)) {
            return $this->json(['success' => false, 'error' => 'No tasks provided'], 400);
        }

        if (!$this->isCsrfTokenValid('bulk', $token)) {
            return $this->json(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }

        $deleted = 0;

        foreach ($taskIds as $taskId) {
            $task = $this->taskRepository->find((int) $taskId);
            if ($task !== null) {
                $this->entityManager->remove($task);
                $deleted++;
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true, 'deleted' => $deleted]);
    }
}
