<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use App\Repository\ProjectRepository;
use App\Utils\PageSetup;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks/kanban')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class KanbanController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskColumnRepository $columnRepository,
        private readonly ProjectRepository $projectRepository
    ) {
    }

    #[Route(path: '', name: 'task_kanban', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $columns = $this->columnRepository->findAllOrdered();

        // Create default columns if none exist
        if (empty($columns)) {
            $this->columnRepository->createDefaultColumns();
            $columns = $this->columnRepository->findAllOrdered();
        }

        $projectId = $request->query->get('project');
        $project = $projectId ? $this->projectRepository->find($projectId) : null;

        $myTasksOnly = $request->query->getBoolean('my_tasks', false);
        $assignee = $myTasksOnly ? $this->getUser() : null;

        $tasksByColumn = $this->taskRepository->findForKanban($columns, $project, $assignee);
        $projects = $this->projectRepository->findAll();

        $page = new PageSetup('Kanban Board');

        return $this->render('@Task/kanban/board.html.twig', [
            'page_setup' => $page,
            'tasksByColumn' => $tasksByColumn,
            'columns' => $columns,
            'projects' => $projects,
            'currentProject' => $project,
            'myTasksOnly' => $myTasksOnly,
        ]);
    }

    #[Route(path: '/move', name: 'task_kanban_move', methods: ['POST'])]
    public function move(Request $request): JsonResponse
    {
        $taskId = (int) $request->request->get('task_id');
        $columnId = (int) $request->request->get('column_id');
        $positions = $request->request->all('positions');

        $task = $this->taskRepository->find($taskId);

        if ($task === null) {
            return $this->json(['success' => false, 'error' => 'Task not found'], 404);
        }

        $column = $this->columnRepository->find($columnId);
        if ($column === null) {
            return $this->json(['success' => false, 'error' => 'Invalid column'], 400);
        }

        $task->setColumn($column);
        $this->taskRepository->save($task);

        if (!empty($positions)) {
            $this->taskRepository->updatePositions($positions);
        }

        return $this->json([
            'success' => true,
            'task' => [
                'id' => $task->getId(),
                'column_id' => $task->getColumn()->getId(),
            ],
        ]);
    }

    #[Route(path: '/reorder', name: 'task_kanban_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $positions = $request->request->all('positions');

        if (empty($positions)) {
            return $this->json(['success' => false, 'error' => 'No positions provided'], 400);
        }

        $this->taskRepository->updatePositions($positions);

        return $this->json(['success' => true]);
    }
}
