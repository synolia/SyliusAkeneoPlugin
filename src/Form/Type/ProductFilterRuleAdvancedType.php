<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductFilterRuleAdvancedType extends AbstractType
{
    public const MODE = 'advanced';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', HiddenType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.mode',
                'data' => self::MODE,
            ])
            ->add('advanced_filter', TextareaType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.advanced_filter',
                'help' => 'sylius.ui.admin.akeneo.product_filter_rules.advanced_help',
            ])
        ;
    }
}
