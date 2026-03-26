<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskAttachment;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Repository\TaskAttachmentRepository;
use KimaiPlugin\TaskBundle\Service\TaskAttachmentService;
use KimaiPlugin\TaskBundle\Service\TaskHistoryService;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/{taskId}/attachments')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskAttachmentController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskAttachmentRepository $attachmentRepository,
        private readonly TaskAttachmentService $attachmentService,
        private readonly TaskHistoryService $historyService
    ) {
    }

    #[Route(path: '', name: 'task_attachment_upload', methods: ['POST'])]
    public function upload(Request $request, int $taskId): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $uploadedFile = $request->files->get('file');

        if ($uploadedFile === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'No file provided'], 400);
            }
            $this->flashError('No file provided');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$uploadedFile->isValid()) {
            $error = $uploadedFile->getErrorMessage();
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Upload error: ' . $error], 400);
            }
            $this->flashError('Upload error: ' . $error);
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        if (!$this->isCsrfTokenValid('attachment_upload' . $taskId, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            $this->flashError('Invalid CSRF token');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        try {
            $attachment = $this->attachmentService->upload($uploadedFile, $task, $this->getUser());
            $this->historyService->logAttachmentAdded($task, $this->getUser(), $attachment->getFilename());

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => true,
                    'attachment' => [
                        'id' => $attachment->getId(),
                        'filename' => $attachment->getFilename(),
                        'size' => $attachment->getSize(),
                        'uploadedAt' => $attachment->getUploadedAt()->format('Y-m-d H:i:s'),
                        'uploadedBy' => $attachment->getUploadedBy()->getDisplayName(),
                    ]
                ]);
            }

            $this->flashSuccess('File uploaded successfully');
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->flashError('Error uploading file: ' . $e->getMessage());
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }
    }

    #[Route(path: '/{id}/download', name: 'task_attachment_download', methods: ['GET'])]
    public function download(int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            throw $this->createNotFoundException('Task not found');
        }

        $attachment = $this->attachmentRepository->find($id);

        if ($attachment === null || $attachment->getTask()->getId() !== $taskId) {
            throw $this->createNotFoundException('Attachment not found');
        }

        $filePath = $this->attachmentService->getFilePath($attachment);

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        return $this->file($filePath, $attachment->getFilename(), 'inline');
    }

    #[Route(path: '/{id}/delete', name: 'task_attachment_delete', methods: ['POST'])]
    public function delete(Request $request, int $taskId, int $id): Response
    {
        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Task not found'], 404);
            }
            throw $this->createNotFoundException('Task not found');
        }

        $attachment = $this->attachmentRepository->find($id);

        if ($attachment === null || $attachment->getTask()->getId() !== $taskId) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Attachment not found'], 404);
            }
            throw $this->createNotFoundException('Attachment not found');
        }

        $currentUser = $this->getUser();
        $isUploader = $attachment->getUploadedBy()->getId() === $currentUser->getId();
        $isAdmin = in_array('ROLE_ADMIN', $currentUser->getRoles(), true);

        if (!$isUploader && !$isAdmin) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Unauthorized'], 403);
            }
            throw $this->createAccessDeniedException('You can only delete your own attachments');
        }

        if (!$this->isCsrfTokenValid('attachment_delete' . $id, $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Invalid CSRF token'], 403);
            }
            return $this->redirectToRoute('task_show', ['id' => $taskId]);
        }

        try {
            $this->attachmentService->delete($attachment);

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->flashSuccess('Attachment deleted successfully');
        } catch (\Exception $e) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => $e->getMessage()], 400);
            }
            $this->flashError('Error deleting attachment: ' . $e->getMessage());
        }

        return $this->redirectToRoute('task_show', ['id' => $taskId]);
    }
}
