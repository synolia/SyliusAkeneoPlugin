<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Extension;

use Sylius\Bundle\TaxonomyBundle\Form\Type\TaxonType;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Synolia\SyliusAkeneoPlugin\Form\EventSubscriber\BuildTaxonAttributesFormSubscriber;
use Synolia\SyliusAkeneoPlugin\Form\Type\TaxonAttributeValueType;

#[AutoconfigureTag('form.type_extension', ['extended_type' => TaxonType::class, 'priority' => 200])]
class TaxonExtension implements FormTypeExtensionInterface
{
    public function __construct(
        private FactoryInterface $taxonAttributeValueFactory,
        #[Autowire('@sylius.translation_locale_provider.immutable')]
        private TranslationLocaleProviderInterface $localeProvider,
    ) {
    }

    /** @inheritDoc */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventSubscriber(new BuildTaxonAttributesFormSubscriber($this->taxonAttributeValueFactory, $this->localeProvider))
            ->add('attributes', CollectionType::class, [
                'entry_type' => TaxonAttributeValueType::class,
                'required' => false,
                'prototype' => true,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function getExtendedType(): string
    {
        return TaxonType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [TaxonType::class];
    }

    /** @inheritDoc */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
    }

    /** @inheritDoc */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
    }

    /** @inheritDoc */
    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
