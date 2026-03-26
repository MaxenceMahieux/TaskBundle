<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Repository\TaskRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskDependencyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $currentTask = $options['current_task'] ?? null;

        $builder
            ->add('blockedBy', EntityType::class, [
                'label' => 'Blocked By',
                'class' => Task::class,
                'choice_label' => 'title',
                'required' => true,
                'query_builder' => function (TaskRepository $repo) use ($currentTask) {
                    $qb = $repo->createQueryBuilder('t')->orderBy('t.title', 'ASC');

                    if ($currentTask !== null) {
                        $qb->andWhere('t.id != :currentTaskId')
                           ->setParameter('currentTaskId', $currentTask->getId());
                    }

                    return $qb;
                },
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_dependency',
            'current_task' => null,
        ]);

        $resolver->setAllowedTypes('current_task', ['null', Task::class]);
    }
}
