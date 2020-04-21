<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;

final class AttributeAkeneoSyliusMappingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('akeneo_attribute', TextType::class)
            ->add('sylius_attribute', AttributeCodeChoiceType::class)
            ->add('part_of_model', ChoiceType::class, [
                'choices' => [
                    'none' => null,
                    ProductInterface::class => ProductInterface::class,
                    ProductTranslationInterface::class => ProductTranslationInterface::class,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AttributeAkeneoSyliusMapping::class,
        ]);
    }
}
