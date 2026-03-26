<?php
namespace KimaiPlugin\TaskBundle\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskAttachment;
use KimaiPlugin\TaskBundle\Repository\TaskAttachmentRepository;
use App\Entity\User;
use DateTime;
use Exception;

class TaskAttachmentService
{
    private string $uploadDir;
    private const MAX_FILE_SIZE = 10485760; // 10MB in bytes

    public function __construct(
        private TaskAttachmentRepository $repository,
        string $projectDir
    ) {
        $this->uploadDir = $projectDir . '/var/data/task_attachments';

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function upload(UploadedFile $file, Task $task, User $user): TaskAttachment
    {
        // Check if file is valid
        if (!$file->isValid()) {
            throw new Exception('Invalid file upload: ' . $file->getErrorMessage());
        }

        // Capture file info BEFORE moving (move() invalidates the original file)
        $fileSize = $file->getSize() ?: 0;
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
        $originalFilename = $file->getClientOriginalName();

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new Exception('File size exceeds maximum allowed size of 10MB');
        }

        if (!in_array($mimeType, $this->getAllowedMimeTypes(), true)) {
            throw new Exception('File type not allowed: ' . $mimeType);
        }

        $safeFilename = $this->generateSafeFilename($originalFilename);
        $timestamp = date('YmdHis');
        $uniqueFilename = $timestamp . '_' . $safeFilename;

        try {
            $file->move($this->uploadDir, $uniqueFilename);
        } catch (FileException $e) {
            throw new Exception('Failed to upload file: ' . $e->getMessage());
        }

        $attachment = new TaskAttachment();
        $attachment->setTask($task);
        $attachment->setUploadedBy($user);
        $attachment->setFilename($originalFilename);
        $attachment->setStoredFilename($uniqueFilename);
        $attachment->setMimeType($mimeType);
        $attachment->setSize($fileSize);
        $attachment->setUploadedAt(new DateTime());

        $this->repository->save($attachment, true);

        return $attachment;
    }

    public function delete(TaskAttachment $attachment): void
    {
        $filePath = $this->getFilePath($attachment);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $this->repository->remove($attachment, true);
    }

    public function getFilePath(TaskAttachment $attachment): string
    {
        return $this->uploadDir . '/' . $attachment->getStoredFilename();
    }

    public function getAllowedMimeTypes(): array
    {
        return [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ];
    }

    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    private function generateSafeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);

        return $filename;
    }
}
