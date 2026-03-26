<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/{taskId}/dependencies')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskDependencyController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository
    ) {
    }

    #[Route(path: '/add', name: 'task_dependency_add', methods: ['POST'])]
    public function add(Request $request, int $taskId): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $blockerId = (int) $request->request->get('blocker_id');
        $blockerTask = $this->taskRepository->find($blockerId);

        if ($blockerTask === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Blocker task not found'], 404);
            }
            $this->flashError('Blocker task not found');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if ($blockerId === $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task cannot block itself'], 400);
            }
            $this->flashError('Task cannot block itself');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$this->isCsrfTokenValid('dependency_add' . $taskId, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            $this->flashError('Invalid CSRF token');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$task->getBlockedBy()->contains($blockerTask)) {
            $task->addBlockedBy($blockerTask);
            $this->taskRepository->save($task);

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'dependency' => [
                        'blockerId' => $blockerTask->getId(),
                        'blockerTitle' => $blockerTask->getTitle(),
                    ]
                ]);
            }

            $this->flashSuccess('Dependency added successfully');
        } else {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Dependency already exists'], 409);
            }
            $this->flashWarning('Dependency already exists');
        }

        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/{blockerId}/remove', name: 'task_dependency_remove', methods: ['POST'])]
    public function remove(Request $request, int $taskId, int $blockerId): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $blockerTask = $this->taskRepository->find($blockerId);

        if ($blockerTask === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Blocker task not found'], 404);
            }
            throw $this->createNotFoundException('Blocker task not found');
        }

        if (!$this->isCsrfTokenValid('dependency_remove' . $blockerId, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if ($task->getBlockedBy()->contains($blockerTask)) {
            $task->removeBlockedBy($blockerTask);
            $this->taskRepository->save($task);

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->flashSuccess('Dependency removed successfully');
        } else {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Dependency not found'], 404);
            }
            $this->flashError('Dependency not found');
        }

        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }
}
