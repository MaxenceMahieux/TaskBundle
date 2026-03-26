<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskSubtask;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Repository\TaskSubtaskRepository;
use KimaiPlugin\TaskBundle\Service\TaskHistoryService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/{taskId}/subtasks')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskSubtaskController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskSubtaskRepository $subtaskRepository,
        private readonly TaskHistoryService $historyService,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '', name: 'task_subtask_add', methods: ['POST'])]
    public function add(Request $request, int $taskId): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $title = $request->request->get('title');

        if (empty($title)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Title is required'], 400);
            }
            $this->flashError('Title is required');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$this->isCsrfTokenValid('subtask_add' . $taskId, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            $this->flashError('Invalid CSRF token');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $position = $this->subtaskRepository->getNextPosition($task);

        $subtask = new TaskSubtask();
        $subtask->setTask($task);
        $subtask->setTitle($title);
        $subtask->setPosition($position);

        $this->subtaskRepository->save($subtask);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'subtask' => [
                    'id' => $subtask->getId(),
                    'title' => $subtask->getTitle(),
                    'isCompleted' => $subtask->isCompleted(),
                    'position' => $subtask->getPosition(),
                ]
            ]);
        }

        $this->flashSuccess('Subtask added successfully');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/{id}/toggle', name: 'task_subtask_toggle', methods: ['POST'])]
    public function toggle(Request $request, int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $subtask = $this->subtaskRepository->find($id);

        if ($subtask === null || $subtask->getTask()->getId() !== $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Subtask not found'], 404);
            }
            throw $this->createNotFoundException('Subtask not found');
        }

        if (!$this->isCsrfTokenValid('subtask_toggle' . $id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $currentUser = $this->getUser();
        $isCompleting = !$subtask->isCompleted();

        $subtask->setIsCompleted($isCompleting);

        if ($isCompleting) {
            $subtask->setCompletedBy($currentUser);
            $subtask->setCompletedAt(new \DateTime());
            $this->historyService->logSubtaskCompleted($task, $currentUser, $subtask->getTitle());
        } else {
            $subtask->setCompletedBy(null);
            $subtask->setCompletedAt(null);
        }

        $this->subtaskRepository->save($subtask);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'isCompleted' => $subtask->isCompleted(),
            ]);
        }

        $this->flashSuccess('Subtask status updated');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/{id}/delete', name: 'task_subtask_delete', methods: ['POST'])]
    public function delete(Request $request, int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $subtask = $this->subtaskRepository->find($id);

        if ($subtask === null || $subtask->getTask()->getId() !== $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Subtask not found'], 404);
            }
            throw $this->createNotFoundException('Subtask not found');
        }

        if (!$this->isCsrfTokenValid('subtask_delete' . $id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $this->subtaskRepository->remove($subtask);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->flashSuccess('Subtask deleted successfully');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/reorder', name: 'task_subtask_reorder', methods: ['POST'])]
    public function reorder(Request $request, int $taskId): JsonResponse
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            return $this->json(['error' => 'Task not found'], 404);
        }

        $positions = $request->request->all('positions');

        if (empty($positions)) {
            return $this->json(['success' => false, 'error' => 'No positions provided'], 400);
        }

        if (!$this->isCsrfTokenValid('subtask_reorder' . $taskId, $request->request->get('_token'))) {
            return $this->json(['error' => 'Invalid CSRF token'], 403);
        }

        foreach ($positions as $position => $subtaskId) {
            $subtask = $this->subtaskRepository->find($subtaskId);
            if ($subtask !== null && $subtask->getTask()->getId() === $taskId) {
                $subtask->setPosition((int) $position);
                $this->subtaskRepository->save($subtask, false);
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }
}
