<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use App\Form\Type\ProjectType;
use App\Form\Type\UserType;
use KimaiPlugin\TaskBundle\Entity\Task;
use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use KimaiPlugin\TaskBundle\Entity\TaskPriority;
use KimaiPlugin\TaskBundle\Repository\TaskColumnRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'placeholder' => 'Enter task title',
                    'autofocus' => 'autofocus',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Enter task description',
                ],
            ])
            ->add('project', ProjectType::class, [
                'label' => 'Project',
                'required' => true,
            ])
            ->add('assignee', UserType::class, [
                'label' => 'Assignee',
                'required' => false,
            ])
            ->add('column', EntityType::class, [
                'label' => 'Status',
                'class' => TaskColumn::class,
                'choice_label' => 'name',
                'query_builder' => function (TaskColumnRepository $repo) {
                    return $repo->createQueryBuilder('c')->orderBy('c.position', 'ASC');
                },
            ])
            ->add('priority', EnumType::class, [
                'label' => 'Priority',
                'class' => TaskPriority::class,
                'choice_label' => fn(TaskPriority $priority) => $priority->getLabel(),
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Due Date',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('estimatedMinutes', IntegerType::class, [
                'label' => 'Estimated Time (minutes)',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => 'e.g. 120',
                ],
            ])
            ->add('isInternal', CheckboxType::class, [
                'label' => 'Internal task (hidden from client portal)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_edit',
        ]);
    }
}
