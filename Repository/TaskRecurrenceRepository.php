<?php

/*
 * This file is part of the Kimai time-tracking app.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\TaskBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\TaskBundle\Entity\TaskRecurrence;

/**
 * @method TaskRecurrence|null find($id, $lockMode = null, $lockVersion = null)
 * @method TaskRecurrence|null findOneBy(array $criteria, array $orderBy = null)
 * @method TaskRecurrence[] findAll()
 * @method TaskRecurrence[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRecurrenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskRecurrence::class);
    }

    /**
     * @return TaskRecurrence[]
     */
    public function findDueForProcessing(DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('tr')
            ->where('tr.nextRunDate <= :date')
            ->andWhere('tr.isActive = true')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function save(TaskRecurrence $recurrence, bool $flush = true): void
    {
        $this->getEntityManager()->persist($recurrence);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskRecurrence $recurrence, bool $flush = true): void
    {
        $this->getEntityManager()->remove($recurrence);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
