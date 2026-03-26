<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Repository\Query;

/**
 * @extends ServiceEntityRepository<Task>
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $task, bool $flush = true): void
    {
        $this->getEntityManager()->persist($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Task $task, bool $flush = true): void
    {
        $this->getEntityManager()->remove($task);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Task[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[]
     */
    public function findByAssignee(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.assignee = :user')
            ->setParameter('user', $user)
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.priority', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Task[]
     */
    public function findByColumn(TaskColumn $column): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.column = :column')
            ->setParameter('column', $column)
            ->orderBy('t.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get tasks grouped by column for Kanban board
     * @param TaskColumn[] $columns
     * @return array<int, Task[]>
     */
    public function findForKanban(array $columns, ?Project $project = null, ?User $assignee = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->orderBy('t.position', 'ASC')
            ->addOrderBy('t.priority', 'DESC');

        if ($project !== null) {
            $qb->andWhere('t.project = :project')
                ->setParameter('project', $project);
        }

        if ($assignee !== null) {
            $qb->andWhere('t.assignee = :assignee')
                ->setParameter('assignee', $assignee);
        }

        $tasks = $qb->getQuery()->getResult();

        $grouped = [];
        foreach ($columns as $column) {
            $grouped[$column->getId()] = [];
        }

        foreach ($tasks as $task) {
            $columnId = $task->getColumn()->getId();
            if (isset($grouped[$columnId])) {
                $grouped[$columnId][] = $task;
            }
        }

        return $grouped;
    }

    /**
     * @return Task[]
     */
    public function findOverdue(): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.column', 'c')
            ->where('t.dueDate < :today')
            ->andWhere('c.isClosedStatus = :closed')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('closed', false)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getNextPosition(TaskColumn $column, ?Project $project = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('MAX(t.position)')
            ->where('t.column = :column')
            ->setParameter('column', $column);

        if ($project !== null) {
            $qb->andWhere('t.project = :project')
                ->setParameter('project', $project);
        }

        $maxPosition = $qb->getQuery()->getSingleScalarResult();

        return ($maxPosition ?? 0) + 1;
    }

    /**
     * Update positions after drag & drop
     */
    public function updatePositions(array $taskIds): void
    {
        $em = $this->getEntityManager();

        foreach ($taskIds as $position => $taskId) {
            $task = $this->find($taskId);
            if ($task !== null) {
                $task->setPosition((int) $position);
                $em->persist($task);
            }
        }

        $em->flush();
    }

    /**
     * @return Task[]
     */
    public function findByQuery(Query\TaskQuery $query): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.column', 'c')
            ->leftJoin('t.project', 'p')
            ->leftJoin('t.assignee', 'a');

        if ($query->getTextSearch() !== null) {
            $searchTerm = '%' . $query->getTextSearch() . '%';
            $qb->andWhere('(t.title LIKE :searchTerm OR t.description LIKE :searchTerm)')
                ->setParameter('searchTerm', $searchTerm);
        }

        if ($query->getProject() !== null) {
            $qb->andWhere('t.project = :project')
                ->setParameter('project', $query->getProject());
        }

        if ($query->getAssignee() !== null) {
            $qb->andWhere('t.assignee = :assignee')
                ->setParameter('assignee', $query->getAssignee());
        }

        if ($query->getColumn() !== null) {
            $qb->andWhere('t.column = :column')
                ->setParameter('column', $query->getColumn());
        }

        if ($query->getPriority() !== null) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $query->getPriority());
        }

        if ($query->getDueDateFrom() !== null) {
            $qb->andWhere('t.dueDate >= :dueDateFrom')
                ->setParameter('dueDateFrom', $query->getDueDateFrom());
        }

        if ($query->getDueDateTo() !== null) {
            $qb->andWhere('t.dueDate <= :dueDateTo')
                ->setParameter('dueDateTo', $query->getDueDateTo());
        }

        if ($query->isOverdue()) {
            $qb->andWhere('t.dueDate < :today')
                ->andWhere('c.isClosedStatus = :closed')
                ->setParameter('today', new \DateTime('today'))
                ->setParameter('closed', false);
        }

        if ($query->isMyTasksOnly() && $query->getCurrentUser() !== null) {
            $qb->andWhere('t.assignee = :currentUser')
                ->setParameter('currentUser', $query->getCurrentUser());
        }

        $orderGroups = $query->getOrderGroups();
        foreach ($orderGroups as $orderBy => $order) {
            $field = match ($orderBy) {
                'title' => 't.title',
                'priority' => 't.priority',
                'dueDate' => 't.dueDate',
                'position' => 't.position',
                'createdAt' => 't.createdAt',
                'updatedAt' => 't.updatedAt',
                default => 't.id',
            };
            $qb->addOrderBy($field, $order);
        }

        $offset = ($query->getPage() - 1) * $query->getPageSize();
        $qb->setFirstResult($offset)
            ->setMaxResults($query->getPageSize());

        return $qb->getQuery()->getResult();
    }

    public function countByQuery(Query\TaskQuery $query): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->leftJoin('t.column', 'c');

        if ($query->getTextSearch() !== null) {
            $searchTerm = '%' . $query->getTextSearch() . '%';
            $qb->andWhere('(t.title LIKE :searchTerm OR t.description LIKE :searchTerm)')
                ->setParameter('searchTerm', $searchTerm);
        }

        if ($query->getProject() !== null) {
            $qb->andWhere('t.project = :project')
                ->setParameter('project', $query->getProject());
        }

        if ($query->getAssignee() !== null) {
            $qb->andWhere('t.assignee = :assignee')
                ->setParameter('assignee', $query->getAssignee());
        }

        if ($query->getColumn() !== null) {
            $qb->andWhere('t.column = :column')
                ->setParameter('column', $query->getColumn());
        }

        if ($query->getPriority() !== null) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $query->getPriority());
        }

        if ($query->getDueDateFrom() !== null) {
            $qb->andWhere('t.dueDate >= :dueDateFrom')
                ->setParameter('dueDateFrom', $query->getDueDateFrom());
        }

        if ($query->getDueDateTo() !== null) {
            $qb->andWhere('t.dueDate <= :dueDateTo')
                ->setParameter('dueDateTo', $query->getDueDateTo());
        }

        if ($query->isOverdue()) {
            $qb->andWhere('t.dueDate < :today')
                ->andWhere('c.isClosedStatus = :closed')
                ->setParameter('today', new \DateTime('today'))
                ->setParameter('closed', false);
        }

        if ($query->isMyTasksOnly() && $query->getCurrentUser() !== null) {
            $qb->andWhere('t.assignee = :currentUser')
                ->setParameter('currentUser', $query->getCurrentUser());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
