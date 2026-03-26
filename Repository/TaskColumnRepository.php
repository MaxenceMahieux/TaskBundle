<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;

/**
 * @extends ServiceEntityRepository<TaskColumn>
 */
class TaskColumnRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TaskColumn::class);
    }

    public function save(TaskColumn $column, bool $flush = true): void
    {
        $this->getEntityManager()->persist($column);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TaskColumn $column, bool $flush = true): void
    {
        $this->getEntityManager()->remove($column);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return TaskColumn[]
     */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDefault(): ?TaskColumn
    {
        return $this->findOneBy(['isDefault' => true]);
    }

    public function getNextPosition(): int
    {
        $maxPosition = $this->createQueryBuilder('c')
            ->select('MAX(c.position)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxPosition ?? 0) + 1;
    }

    public function createDefaultColumns(): void
    {
        $defaults = [
            ['name' => 'To Do', 'slug' => 'todo', 'color' => '#6c757d', 'isDefault' => true],
            ['name' => 'In Progress', 'slug' => 'in_progress', 'color' => '#0d6efd'],
            ['name' => 'Review', 'slug' => 'review', 'color' => '#ffc107'],
            ['name' => 'Done', 'slug' => 'done', 'color' => '#198754', 'isClosedStatus' => true],
        ];

        $position = 0;
        foreach ($defaults as $data) {
            $column = new TaskColumn();
            $column->setName($data['name']);
            $column->setSlug($data['slug']);
            $column->setColor($data['color']);
            $column->setPosition($position++);
            $column->setIsDefault($data['isDefault'] ?? false);
            $column->setIsClosedStatus($data['isClosedStatus'] ?? false);

            $this->save($column, false);
        }

        $this->getEntityManager()->flush();
    }
}
