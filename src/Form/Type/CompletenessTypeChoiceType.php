<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Akeneo\Pim\ApiClient\Search\Operator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CompletenessTypeChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $completenessType = [
            'sylius.ui.admin.akeneo.product_filter_rules.lower_than' => Operator::LOWER_THAN,
            'sylius.ui.admin.akeneo.product_filter_rules.greater_than' => Operator::GREATER_THAN,
            'sylius.ui.admin.akeneo.product_filter_rules.greater_or_equals_than' => Operator::GREATER_THAN_OR_EQUAL,
            'sylius.ui.admin.akeneo.product_filter_rules.equals' => Operator::EQUAL,
            'sylius.ui.admin.akeneo.product_filter_rules.differ' => Operator::NOT_EQUAL,
            'sylius.ui.admin.akeneo.product_filter_rules.greater_than_on_all_locales' => Operator::GREATER_THAN_ON_ALL_LOCALES,
            'sylius.ui.admin.akeneo.product_filter_rules.greater_or_equals_than_on_all_locales' => Operator::GREATER_OR_EQUALS_THAN_ON_ALL_LOCALES,
            'sylius.ui.admin.akeneo.product_filter_rules.lower_than_on_all_locales' => Operator::LOWER_THAN_ON_ALL_LOCALES,
            'sylius.ui.admin.akeneo.product_filter_rules.lower_or_equals_than_on_all_locales' => Operator::LOWER_OR_EQUALS_THAN_ON_ALL_LOCALES,
        ];

        $resolver->setDefaults(['choices' => $completenessType]);
    }

    public function getParent()
    {
        return ChoiceType::class;
    }
}
