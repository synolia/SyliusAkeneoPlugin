<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAkeneoImageAttribute;

final class ProductImageAttributesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('akeneoAttributes', TextType::class, ['label' => 'sylius.ui.admin.akeneo.products.akeneo_attributes'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductConfigurationAkeneoImageAttribute::class]);
    }
}
