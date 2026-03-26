<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use App\Form\Type\ProjectType;
use App\Form\Type\UserType;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Entity\TaskPriority;
use KimaiPlugin\TaskBundle\Repository\Query\TaskQuery;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskQueryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('textSearch', TextType::class, [
                'label' => 'Search',
                'required' => false,
                'attr' => [
                    'placeholder' => 'Search tasks...',
                ],
            ])
            ->add('project', ProjectType::class, [
                'label' => 'Project',
                'required' => false,
            ])
            ->add('assignee', UserType::class, [
                'label' => 'Assignee',
                'required' => false,
            ])
            ->add('column', EntityType::class, [
                'class' => TaskColumn::class,
                'choice_label' => 'name',
                'label' => 'Status',
                'required' => false,
                'placeholder' => 'All Statuses',
            ])
            ->add('priority', EnumType::class, [
                'class' => TaskPriority::class,
                'choice_label' => fn(TaskPriority $priority) => $priority->getLabel(),
                'label' => 'Priority',
                'required' => false,
                'placeholder' => 'All Priorities',
            ])
            ->add('dueDateFrom', DateType::class, [
                'label' => 'task_bundle.due_date_from',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('dueDateTo', DateType::class, [
                'label' => 'task_bundle.due_date_to',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('isOverdue', CheckboxType::class, [
                'label' => 'task_bundle.overdue_only',
                'required' => false,
            ])
            ->add('myTasksOnly', CheckboxType::class, [
                'label' => 'task_bundle.my_tasks_only',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskQuery::class,
            'csrf_protection' => false,
        ]);
    }
}
