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
use KimaiPlugin\TaskBundle\Entity\TaskComment;

/**
 * @method TaskComment|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskComment|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskComment[] findAll()
 * @method TaskComment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskComment::class);
    }

    /**
     * @return TaskComment[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('tc')
            ->where('tc.task = :task')
            ->setParameter('task', $task)
            ->orderBy('tc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TaskComment[]
     */
    public function findPinnedByTask(Task $task): array
    {
        return $this->createQueryBuilder('tc')
            ->where('tc.task = :task')
            ->andWhere('tc.isPinned = true')
            ->setParameter('task', $task)
            ->orderBy('tc.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TaskComment $comment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($comment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskComment $comment, bool $flush = true): void
    {
        $this->getEntityManager()->remove($comment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
