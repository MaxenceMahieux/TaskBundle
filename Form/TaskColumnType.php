<?php

declare(strict_types=1);

namespace KimaiPlugin\TaskBundle\Form;

use KimaiPlugin\TaskBundle\Entity\TaskColumn;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskColumnType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['autofocus' => 'autofocus'],
            ])
            ->add('color', ColorType::class, [
                'label' => 'Color',
            ])
            ->add('isDefault', CheckboxType::class, [
                'label' => 'Default column for new tasks',
                'required' => false,
            ])
            ->add('isClosedStatus', CheckboxType::class, [
                'label' => 'Closed status (tasks are considered done)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TaskColumn::class,
            'csrf_protection' => true,
        ]);
    }
}
