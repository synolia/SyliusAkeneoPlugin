<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Search\Operator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Synolia\SyliusAkeneoPlugin\Enum\ProductFilterStatusEnum;

final class ProductFilterRuleSimpleType extends AbstractType
{
    public const MODE = 'simple';

    public const MIN_COMPLETENESS = 0;

    public const MAX_COMPLETENESS = 100;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mode', HiddenType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.mode',
                'data' => self::MODE,
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.channel',
            ])
            ->add('completeness_type', CompletenessTypeChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.completeness_type',
                'placeholder' => 'sylius.ui.admin.akeneo.product_filter_rules.no_condition',
                'required' => false,
            ])
            ->add('locales', LocalesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.locales',
            ])
            ->add('completeness_value', IntegerType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.completeness_value',
                'constraints' => [
                    new Assert\Range([
                        'min' => self::MIN_COMPLETENESS,
                        'max' => self::MAX_COMPLETENESS,
                    ]),
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.status',
                'choices' => [
                    'sylius.ui.admin.akeneo.product_filter_rules.no_condition' => ProductFilterStatusEnum::NO_CONDITION,
                    'sylius.ui.admin.akeneo.product_filter_rules.enabled' => ProductFilterStatusEnum::ENABLED,
                    'sylius.ui.admin.akeneo.product_filter_rules.disabled' => ProductFilterStatusEnum::DISABLED,
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
                'widget' => 'single_text',
            ])
            ->add('updated_after', DateType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated_after',
                'widget' => 'single_text',
            ])
            ->add('updated', IntegerType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.updated',
                'required' => false,
            ])
            ->add('exclude_families', FamiliesChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.product_filter_rules.families',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius.ui.save',
                'attr' => ['class' => 'ui primary button'],
            ])
        ;
    }
}
