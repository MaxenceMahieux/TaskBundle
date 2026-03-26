<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\TaskBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskSubtask;

/**
 * @method TaskSubtask|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskSubtask|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskSubtask[] findAll()
 * @method TaskSubtask[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskSubtaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskSubtask::class);
    }

    /**
     * @return TaskSubtask[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.task = :task')
            ->setParameter('task', $task)
            ->orderBy('ts.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TaskSubtask[]
     */
    public function findCompletedByTask(Task $task): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.task = :task')
            ->andWhere('ts.isCompleted = true')
            ->setParameter('task', $task)
            ->orderBy('ts.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TaskSubtask[]
     */
    public function findPendingByTask(Task $task): array
    {
        return $this->createQueryBuilder('ts')
            ->where('ts.task = :task')
            ->andWhere('ts.isCompleted = false')
            ->setParameter('task', $task)
            ->orderBy('ts.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(TaskSubtask $subtask, bool $flush = true): void
    {
        $this->getEntityManager()->persist($subtask);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskSubtask $subtask, bool $flush = true): void
    {
        $this->getEntityManager()->remove($subtask);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getNextPosition(Task $task): int
    {
        $result = $this->createQueryBuilder('ts')
            ->select('MAX(ts.position) as maxPosition')
            ->where('ts.task = :task')
            ->setParameter('task', $task)
            ->getQuery()
            ->getOneOrNullResult();

        $currentMax = $result['maxPosition'] ?? 0;
        return (int)$currentMax + 1;
    }
}
