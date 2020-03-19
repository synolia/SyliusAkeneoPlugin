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
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('websiteAttribute', TextType::class, ['label' => 'sylius.ui.admin.akeneo.products.website_attribute'])
            ->add('defaultTax', CollectionType::class, [
                'required' => true,
                'mapped' => true,
                'label' => false,
                'entry_type' => ProductDefaultTaxType::class,
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
                'entry_type' => ProductAttributsType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ])
            ->add('importMediaFiles', CheckboxType::class, [
                'label' => 'sylius.ui.admin.akeneo.products.import_media_files',
            ])
            ->add('akeneoImageAttributes', CollectionType::class, [
                'required' => true,
                'mapped' => true,
                'label' => false,
                'entry_type' => ProductImageAttributesType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ])
            ->add('productImagesMapping', CollectionType::class, [
                'required' => true,
                'mapped' => true,
                'label' => false,
                'entry_type' => ProductImagesMappingType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'error_bubbling' => false,
            ])
            ->add('regenerateUrlRewrites', CheckboxType::class, [
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
