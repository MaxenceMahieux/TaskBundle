<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskComment;
use KimaiPlugin\TaskBundle\Repository\TaskCommentRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Service\TaskHistoryService;
use KimaiPlugin\TaskBundle\Service\TaskNotificationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/{taskId}/comments')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskCommentController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskCommentRepository $commentRepository,
        private readonly TaskHistoryService $historyService,
        private readonly TaskNotificationService $notificationService
    ) {
    }

    #[Route(path: '', name: 'task_comment_add', methods: ['POST'])]
    public function add(Request $request, int $taskId): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $message = $request->request->get('message');

        if (empty($message)) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Message is required'], 400);
            }
            $this->flashError('Message is required');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$this->isCsrfTokenValid('comment_add' . $taskId, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            $this->flashError('Invalid CSRF token');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $comment = new TaskComment();
        $comment->setTask($task);
        $comment->setMessage($message);
        $comment->setCreatedBy($this->getUser());

        $this->commentRepository->save($comment);
        $this->historyService->logCommentAdded($task, $this->getUser());
        $this->notificationService->notifyCommentAdded($task, $this->getUser());

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'comment' => [
                    'id' => $comment->getId(),
                    'message' => $comment->getMessage(),
                    'createdBy' => $comment->getCreatedBy()->getDisplayName(),
                    'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                    'isPinned' => $comment->isPinned(),
                ]
            ]);
        }

        $this->flashSuccess('Comment added successfully');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/{id}/pin', name: 'task_comment_pin', methods: ['POST'])]
    public function pin(Request $request, int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $comment = $this->commentRepository->find($id);

        if ($comment === null || $comment->getTask()->getId() !== $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Comment not found'], 404);
            }
            throw $this->createNotFoundException('Comment not found');
        }

        if (!$this->isCsrfTokenValid('comment_pin' . $id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $comment->setIsPinned(!$comment->isPinned());
        $this->commentRepository->save($comment);

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => true,
                'isPinned' => $comment->isPinned(),
            ]);
        }

        $this->flashSuccess('Comment pin status updated');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }

    #[Route(path: '/{id}/delete', name: 'task_comment_delete', methods: ['POST'])]
    public function delete(Request $request, int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $comment = $this->commentRepository->find($id);

        if ($comment === null || $comment->getTask()->getId() !== $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Comment not found'], 404);
            }
            throw $this->createNotFoundException('Comment not found');
        }

        $currentUser = $this->getUser();
        $isAuthor = $comment->getCreatedBy()->getId() === $currentUser->getId();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles(), true);

        if (!$isAuthor && !$isAdmin) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
            throw $this->createAccessDeniedException('You can only delete your own comments');
        }

        if (!$this->isCsrfTokenValid('comment_delete' . $id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        $this->commentRepository->remove($comment);

        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true]);
        }

        $this->flashSuccess('Comment deleted successfully');
        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }
}
