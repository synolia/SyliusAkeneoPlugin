<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class CategoriesConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('root_categories', CategoriesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.root_categories',
                'multiple' => true,
                'required' => false,
            ])
            ->add('not_import_categories', CategoriesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.categories_to_exclude',
                'required' => false,
                'multiple' => true,
            ])
            ->add('use_akeneo_positions', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.use_akeneo_positions',
                'required' => false,
            ])
        ;
    }
}
