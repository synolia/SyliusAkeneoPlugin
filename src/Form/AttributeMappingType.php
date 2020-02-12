<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeMapping;

final class AttributeMappingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sylius', ChoiceType::class, ['label' => 'Sylius'])
            ->add('akeneo', ChoiceType::class, ['label' => 'Akeneo'])
            ->add('translate', CheckboxType::class, ['label' => 'Translate'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => AttributeMapping::class]);
    }
}
