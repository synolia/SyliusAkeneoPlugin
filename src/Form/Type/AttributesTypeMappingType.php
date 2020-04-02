<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AttributesTypeMappingType extends AbstractType
{
    public const TYPE_MAPPINGS_CODE = 'attributeTypeMappings';

    public const AKENEO_SYLIUS_MAPPINGS_CODE = 'attributeAkeneoSyliusMappings';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('settings', SettingsType::class, [
                'data' => $options['data']['settings'],
            ])
            ->add(self::TYPE_MAPPINGS_CODE, CollectionType::class, [
                'required' => false,
                'entry_type' => AttributeTypeMappingType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Attributes Types Mapping',
            ])
            ->add(self::AKENEO_SYLIUS_MAPPINGS_CODE, CollectionType::class, [
                'required' => false,
                'entry_type' => AttributeAkeneoSyliusMappingType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => 'Attributes Akeneo to Sylius Mapping',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius.ui.save',
                'attr' => ['class' => 'ui primary button'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data' => ['settings' => []]]);
    }
}
