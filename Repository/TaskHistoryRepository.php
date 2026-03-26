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
use KimaiPlugin\TaskBundle\Entity\TaskHistory;

/**
 * @method TaskHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskHistory[] findAll()
 * @method TaskHistory[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskHistory::class);
    }

    /**
     * @return TaskHistory[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('th')
            ->where('th.task = :task')
            ->setParameter('task', $task)
            ->orderBy('th.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TaskHistory $history, bool $flush = true): void
    {
        $this->getEntityManager()->persist($history);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
