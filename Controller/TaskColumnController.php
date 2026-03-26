<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Controller;

use App\Controller\AbstractController;
use App\Utils\PageSetup;
use Doctrine\ORM\EntityManagerInterface;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Form\TaskColumnType;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/tasks/columns')]
#[IsGranted('ROLE_ADMIN')]
class TaskColumnController extends AbstractController
{
    public function __construct(
        private readonly TaskColumnRepository $columnRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '', name: 'task_column_index', methods: ['GET'])]
    public function index(): Response
    {
        $columns = $this->columnRepository->findAllOrdered();

        // Create default columns if none exist
        if (empty($columns)) {
            $this->columnRepository->createDefaultColumns();
            $columns = $this->columnRepository->findAllOrdered();
        }

        $page = new PageSetup('Task Columns');

        return $this->render('@Task/column/index.html.twig', [
            'page_setup' => $page,
            'columns' => $columns,
        ]);
    }

    #[Route(path: '/create', name: 'task_column_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        $column = new TaskColumn();
        $column->setPosition($this->columnRepository->getNextPosition());

        $form = $this->createForm(TaskColumnType::class, $column);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Generate slug from name
            $slug = $this->generateSlug($column->getName());
            $column->setSlug($slug);

            // If this is set as default, unset others
            if ($column->isDefault()) {
                $this->unsetOtherDefaults();
            }

            $this->columnRepository->save($column);
            $this->flashSuccess('Column created successfully');

            return $this->redirectToRoute('task_column_index');
        }

        $page = new PageSetup('Create Column');

        return $this->render('@Task/column/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'column' => $column,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'task_column_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TaskColumn $column): Response
    {
        $form = $this->createForm(TaskColumnType::class, $column);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // If this is set as default, unset others
            if ($column->isDefault()) {
                $this->unsetOtherDefaults($column->getId());
            }

            $this->columnRepository->save($column);
            $this->flashSuccess('Column updated successfully');

            return $this->redirectToRoute('task_column_index');
        }

        $page = new PageSetup('Edit Column');

        return $this->render('@Task/column/edit.html.twig', [
            'page_setup' => $page,
            'form' => $form->createView(),
            'column' => $column,
        ]);
    }

    #[Route(path: '/{id}/delete', name: 'task_column_delete', methods: ['POST'])]
    public function delete(Request $request, TaskColumn $column): Response
    {
        if ($this->isCsrfTokenValid('delete' . $column->getId(), $request->request->get('_token'))) {
            $this->columnRepository->remove($column);
            $this->flashSuccess('Column deleted successfully');
        }

        return $this->redirectToRoute('task_column_index');
    }

    #[Route(path: '/reorder', name: 'task_column_reorder', methods: ['POST'])]
    public function reorder(Request $request): JsonResponse
    {
        $positions = $request->request->all('positions');

        if (empty($positions)) {
            return $this->json(['success' => false, 'error' => 'No positions provided'], 400);
        }

        foreach ($positions as $position => $columnId) {
            $column = $this->columnRepository->find($columnId);
            if ($column !== null) {
                $column->setPosition((int) $position);
                $this->columnRepository->save($column, false);
            }
        }

        $this->entityManager->flush();

        return $this->json(['success' => true]);
    }

    private function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');

        // Check for uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while ($this->columnRepository->findOneBy(['slug' => $slug]) !== null) {
            $slug = $baseSlug . '_' . $counter++;
        }

        return $slug;
    }

    private function unsetOtherDefaults(?int $excludeId = null): void
    {
        $columns = $this->columnRepository->findBy(['isDefault' => true]);
        foreach ($columns as $col) {
            if ($excludeId === null || $col->getId() !== $excludeId) {
                $col->setIsDefault(false);
                $this->columnRepository->save($col, false);
            }
        }
        $this->entityManager->flush();
    }
}
