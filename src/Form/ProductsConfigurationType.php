<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProductsConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('website_attribute', TextType::class, ['label' => 'sylius.ui.admin.akeneo.products.website_attribute'])
//            ->add('attribute_mapping', CollectionType::class, [
//                'required' => true,
//                'mapped' => true,
//                'label' => false,
//                'entry_type' => AttributeMappingType::class,
//                'entry_options' => ['label' => false],
//                'allow_add' => true,
//                'allow_delete' => true,
//                'by_reference' => false,
//                'error_bubbling' => false,
//            ])
            ->add('default_tax', CollectionType::class, [
                'required' => true,
                'mapped' => true,
                'label' => false,
                'entry_type' => ProductsDefaultTaxType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ])
            ->add('configurable', CollectionType::class, [
                'required' => true,
                'mapped' => true,
                'label' => false,
                'entry_type' => ProductsAttributsType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ])
            ->add('import_media_files', CheckboxType::class, ['label' => 'sylius.ui.admin.akeneo.products.import_media_files'])
            ->add('akeneo_image_attributes', ChoiceType::class, ['label' => 'sylius.ui.admin.akeneo.products.import_media_files'])
            ->add('product_images_mapping', ChoiceType::class, ['label' => 'sylius.ui.admin.akeneo.products.import_media_files'])
            ->add('import_asset_files', CheckboxType::class, ['label' => 'sylius.ui.admin.akeneo.products.import_asset_files'])
            ->add('akeneo_asset_attributes', ChoiceType::class, ['label' => 'sylius.ui.admin.akeneo.products.import_asset_files'])
            ->add('regenerate_url_rewrites', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.regenerate_url_rewrites',
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'sylius.ui.admin.akeneo.submit',
                'attr' => [
                    'class' => 'ui  icon button  primary',
                ],
            ])
        ;
    }
}
