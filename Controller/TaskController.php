<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use App\Repository\UserRepository;
use App\Utils\PageSetup;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Form\TaskType;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use KimaiPlugin\TaskBundle\Repository\Query\TaskQuery;
use KimaiPlugin\TaskBundle\Service\TaskTimesheetService;
use KimaiPlugin\TaskBundle\Service\TaskHistoryService;
use KimaiPlugin\TaskBundle\Service\TaskNotificationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/tasks')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class TaskController extends AbstractController
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly TaskColumnRepository $columnRepository,
        private readonly TaskTimesheetService $timesheetService,
        private readonly TaskHistoryService $historyService,
        private readonly TaskNotificationService $notificationService,
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route(path: '', name: 'task_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $query = new TaskQuery();

        $searchTerm = $request->query->get('search');
        if (!empty($searchTerm)) {
            $query->setTextSearch($searchTerm);
        }

        $columnId = $request->query->get('column');
        if (!empty($columnId)) {
            $column = $this->columnRepository->find((int) $columnId);
            if ($column !== null) {
                $query->setColumn($column);
            }
        }

        $assigneeId = $request->query->get('assignee');
        if (!empty($assigneeId)) {
            $assignee = $this->userRepository->find((int) $assigneeId);
            if ($assignee !== null) {
                $query->setAssignee($assignee);
            }
        }

        $myTasks = $request->query->get('my_tasks');
        if ($myTasks === '1') {
            $query->setMyTasksOnly(true);
            $query->setCurrentUser($this->getUser());
        }

        $tasks = $this->taskRepository->findByQuery($query);
        $columns = $this->columnRepository->findAllOrdered();
        $users = $this->userRepository->findAll();

        $page = new PageSetup('Tasks');

        return $this->render('@Task/task/index.html.twig', [
            'page_setup' => $page,
            'tasks' => $tasks,
            'query' => $query,
            'columns' => $columns,
            'users' => $users,
        ]);
    }

    #[Route(path: '/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        // Ensure columns exist
        $columns = $this->columnRepository->findAllOrdered();
        if (empty($columns)) {
            $this->columnRepository->createDefaultColumns();
            $columns = $this->columnRepository->findAllOrdered();
        }

        $defaultColumn = $this->columnRepository->findDefault() ?? $columns[0];

        $task = new Task();
        $task->setCreatedBy($this->getUser());
        $task->setColumn($defaultColumn);
        $task->setPosition($this->taskRepository->getNextPosition($defaultColumn));

        $form = $this->createForm(TaskType::class, $task, [
            'action' => $this->generateUrl('task_create'),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->taskRepository->save($task);
            $this->historyService->logCreated($task, $this->getUser());

            if ($task->getAssignee() !== null) {
                $this->notificationService->notifyAssigned($task);
            }

            $this->flashSuccess('Task created successfully');

            return $this->redirectToRoute('task_index');
        }

        $page = new PageSetup('Create Task');

        return $this->render('@Task/task/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route(path: '/{id}', name: 'task_show', methods: ['GET'])]
    public function show(Task $task): Response
    {
        $page = new PageSetup($task->getTitle());

        $user = $this->getUser();
        $totalDuration = $this->timesheetService->getTotalDuration($task);
        $activeTimesheet = $this->timesheetService->getActiveTimesheet($task, $user);
        $timesheets = $this->timesheetService->getTimesheets($task);

        return $this->render('@Task/task/show.html.twig', [
            'page_setup' => $page,
            'task' => $task,
            'totalDuration' => $totalDuration,
            'totalDurationFormatted' => TaskTimesheetService::formatDuration($totalDuration),
            'activeTimesheet' => $activeTimesheet,
            'timesheets' => $timesheets,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task): Response
    {
        $form = $this->createForm(TaskType::class, $task, [
            'action' => $this->generateUrl('task_edit', ['id' => $task->getId()]),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oldAssignee = $task->getAssignee();
            $this->taskRepository->save($task);

            $this->historyService->logUpdated($task, $this->getUser(), 'title', null, $task->getTitle());

            $newAssignee = $task->getAssignee();
            if ($oldAssignee !== $newAssignee && $newAssignee !== null) {
                $this->historyService->logAssigned($task, $this->getUser(), $oldAssignee, $newAssignee);
                $this->notificationService->notifyAssigned($task);
            }

            $this->flashSuccess('Task updated successfully');

            return $this->redirectToRoute('task_index');
        }

        $page = new PageSetup('Edit Task');

        return $this->render('@Task/task/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task): Response
    {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {
            $this->taskRepository->remove($task);
            $this->flashSuccess('Task deleted successfully');
        }

        return $this->redirectToRoute('task_index');
    }

    #[Route(path: '/{id}/column', name: 'task_update_column', methods: ['POST'])]
    public function updateColumn(Request $request, Task $task): Response
    {
        $columnId = (int) $request->request->get('column_id');
        $column = $this->columnRepository->find($columnId);

        if ($column !== null) {
            $task->setColumn($column);
            $this->taskRepository->save($task);

            if ($request->isXmlHttpRequest()) {
                return $this->json(['success' => true]);
            }

            $this->flashSuccess('Task status updated');
        }

        return $this->redirectToRoute('task_index');
    }
}
