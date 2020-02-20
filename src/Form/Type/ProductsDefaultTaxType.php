<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\ProductsConfigurationDefaultTax;

final class ProductsDefaultTaxType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('website', ChoiceType::class, ['choices' => ['yes' => 'yes']])
            ->add('taxClass', ChoiceType::class, ['choices' => ['yes' => 'yes']])
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
        $resolver->setDefaults(['data_class' => ProductsConfigurationDefaultTax::class]);
    }
}
