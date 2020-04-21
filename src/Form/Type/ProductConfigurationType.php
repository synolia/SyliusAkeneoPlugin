<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductConfigurationType extends AbstractType
{
    public const AKENEO_IMAGE_ATTRIBUTES_MAPPINGS_CODE = 'akeneoImageAttributes';

    public const PRODUCT_IMAGES_MAPPINGS_CODE = 'productImagesMapping';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('akeneoPriceAttribute', AttributeCodeChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.akeneo_price_attribute',
            ])
            ->add('akeneoEnabledChannelsAttribute', AttributeCodeChoiceType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.akeneo_enabled_channels_attribute',
            ])
            ->add('importMediaFiles', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.import_media_files',
            ])
            ->add(self::AKENEO_IMAGE_ATTRIBUTES_MAPPINGS_CODE, CollectionType::class, [
                'required' => true,
                'entry_type' => ProductImageAttributesType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add(self::PRODUCT_IMAGES_MAPPINGS_CODE, CollectionType::class, [
                'required' => true,
                'entry_type' => ProductImagesMappingType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])
            ->add('regenerateUrlRewrites', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.regenerate_url_rewrites',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius.ui.admin.akeneo.save',
                'attr' => [
                    'class' => 'ui icon button primary',
                ],
            ])
        ;
    }
}
