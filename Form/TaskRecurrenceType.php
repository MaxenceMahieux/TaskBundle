<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskRecurrenceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('frequency', ChoiceType::class, [
                'label' => 'Frequency',
                'required' => true,
                'choices' => [
                    'Daily' => 'daily',
                    'Weekly' => 'weekly',
                    'Monthly' => 'monthly',
                    'Yearly' => 'yearly',
                ],
            ])
            ->add('interval', IntegerType::class, [
                'label' => 'Interval',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'value' => 1,
                ],
            ])
            ->add('daysOfWeek', ChoiceType::class, [
                'label' => 'Days of Week',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'Monday' => 'monday',
                    'Tuesday' => 'tuesday',
                    'Wednesday' => 'wednesday',
                    'Thursday' => 'thursday',
                    'Friday' => 'friday',
                    'Saturday' => 'saturday',
                    'Sunday' => 'sunday',
                ],
            ])
            ->add('dayOfMonth', IntegerType::class, [
                'label' => 'Day of Month',
                'required' => false,
                'attr' => [
                    'min' => 1,
                    'max' => 31,
                ],
            ])
            ->add('startDate', DateType::class, [
                'label' => 'Start Date',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'End Date',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'task_recurrence',
        ]);
    }
}
