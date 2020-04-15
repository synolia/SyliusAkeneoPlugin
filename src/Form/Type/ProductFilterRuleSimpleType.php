<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Search\Operator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductFilterRuleSimpleType extends AbstractType
{
    public const MODE = 'simple';

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
            ->add('channel', ChannelChoiceType::class)
            ->add('completeness_type', CompletenessTypeChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.completeness_type',
            ])
            ->add('locales', LocalesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.locales',
                'multiple' => true,
            ])
            ->add('completeness_value', TextType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.completeness_value',
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.status',
                'choices' => [
                    'sylius.ui.admin.akeneo.product_filter_rules.no_condition' => null,
                    'sylius.ui.admin.akeneo.product_filter_rules.enabled' => true,
                    'sylius.ui.admin.akeneo.product_filter_rules.disabled' => false,
                ],
            ])
            ->add('updated_mode', ChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated_mode',
                'choices' => [
                    'sylius.ui.admin.akeneo.product_filter_rules.lower_than' => Operator::LOWER_THAN,
                    'sylius.ui.admin.akeneo.product_filter_rules.greater_than' => Operator::GREATER_THAN,
                    'sylius.ui.admin.akeneo.product_filter_rules.between' => Operator::BETWEEN,
                    'sylius.ui.admin.akeneo.product_filter_rules.since_last_x_days' => Operator::SINCE_LAST_N_DAYS,
                ],
            ])
            ->add('updated_before', DateType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated_before',
            ])
            ->add('updated_after', DateType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated_after',
            ])
            ->add('updated', TextType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated',
                'required' => false,
            ])
            ->add('families', FamiliesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.families',
            ])
            ->add('submit', SubmitType::class)
        ;
    }
}
