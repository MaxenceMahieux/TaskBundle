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
use KimaiPlugin\TaskBundle\Entity\TaskAttachment;

/**
 * @method TaskAttachment|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskAttachment|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskAttachment[] findAll()
 * @method TaskAttachment[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskAttachment::class);
    }

    /**
     * @return TaskAttachment[]
     */
    public function findByTask(Task $task): array
    {
        return $this->createQueryBuilder('ta')
            ->where('ta.task = :task')
            ->setParameter('task', $task)
            ->orderBy('ta.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(TaskAttachment $attachment, bool $flush = true): void
    {
        $this->getEntityManager()->persist($attachment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskAttachment $attachment, bool $flush = true): void
    {
        $this->getEntityManager()->remove($attachment);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
