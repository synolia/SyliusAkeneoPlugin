<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfigurationAttribute;

final class ProductAttributsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('attribute', TextType::class, ['label' => 'sylius.ui.admin.akeneo.products.attribute'])
            ->add('value', TextType::class, ['label' => 'sylius.ui.admin.akeneo.products.value'])
            ->add('delete', ButtonType::class, [
                'label' => 'sylius.ui.admin.akeneo.delete',
                'attr' => [
                    'class' => 'ui red icon button delete',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProductConfigurationAttribute::class]);
    }
}
