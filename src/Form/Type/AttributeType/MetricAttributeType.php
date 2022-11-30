<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type\AttributeType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Form\DataTransformer\MetricDataTransformer;

final class MetricAttributeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $transformer = new MetricDataTransformer();
        $builder->addModelTransformer($transformer);
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'label' => false,
            ])
            ->setDefined('configuration')
            ->setDefined('locale_code')
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_attribute_type_metric';
    }
}
