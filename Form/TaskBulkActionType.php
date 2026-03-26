<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use App\Form\Type\UserType;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Entity\TaskPriority;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskBulkActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('tasks', HiddenType::class, [
                'label' => 'Tasks',
                'required' => true,
                'attr' => [
                    'data-format' => 'json',
                ],
            ])
            ->add('action', ChoiceType::class, [
                'label' => 'Action',
                'required' => true,
                'choices' => [
                    'Move' => 'move',
                    'Assign' => 'assign',
                    'Delete' => 'delete',
                    'Change Priority' => 'priority',
                ],
            ])
            ->add('column', EntityType::class, [
                'label' => 'Column',
                'class' => TaskColumn::class,
                'choice_label' => 'name',
                'required' => false,
                'query_builder' => function (TaskColumnRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.position', 'ASC');
                },
            ])
            ->add('assignee', UserType::class, [
                'label' => 'Assignee',
                'required' => false,
            ])
            ->add('priority', EnumType::class, [
                'label' => 'Priority',
                'class' => TaskPriority::class,
                'choice_label' => fn(TaskPriority $priority) => $priority->getLabel(),
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_bulk_action',
        ]);
    }
}
