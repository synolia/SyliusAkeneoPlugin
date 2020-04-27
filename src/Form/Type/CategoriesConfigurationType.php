<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

final class CategoriesConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('not_import_categories', CategoriesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.categories_to_exclude',
                'required' => false,
                'multiple' => true,
            ])
            ->add('root_category', CategoriesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.categories.root_category',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => ['class' => 'ui icon primary button'],
                'label' => 'sylius.ui.admin.akeneo.save',
            ])
        ;
    }
}
