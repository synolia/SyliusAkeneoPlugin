<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class CategoriesConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('active_new_categories', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.activate_new_categories',
            ])
            ->add('not_import_categories', CategoriesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.categories',
                'multiple' => true,
            ])
            ->add('main_category', TextType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.main_category',
            ])
            ->add('root_category', TextType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.root_category',
            ])
            ->add('empty_local_replace_by', TextType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.empty_local_replace_by',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'ui icon primary button'],
                'label' => 'sylius.ui.admin.akeneo.save',
            ])
        ;
    }
}
