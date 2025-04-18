<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\EventSubscriber;

use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Translation\Provider\TranslationLocaleProviderInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValueInterface;
use Webmozart\Assert\Assert;

class BuildTaxonAttributesFormSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private FactoryInterface $taxonAttributeValueFactory,
        #[Autowire('@sylius.translation_locale_provider.immutable')]
        private TranslationLocaleProviderInterface $localeProvider,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit',
        ];
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function preSetData(FormEvent $event): void
    {
        $taxon = $event->getData();

        Assert::isInstanceOf($taxon, TaxonAttributeSubjectInterface::class);
        Assert::isInstanceOf($taxon, TaxonInterface::class);

        $defaultLocaleCode = $this->localeProvider->getDefaultLocaleCode();

        $attributes = $taxon->getAttributes()->filter(
            fn (TaxonAttributeValueInterface $attribute) => $attribute->getLocaleCode() === $defaultLocaleCode || $attribute->getLocaleCode() === null,
        );

        /** @var TaxonAttributeValueInterface $attribute */
        foreach ($attributes as $attribute) {
            $this->resolveLocalizedAttributes($taxon, $attribute);
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function postSubmit(FormEvent $event): void
    {
        $taxon = $event->getData();

        Assert::isInstanceOf($taxon, TaxonAttributeSubjectInterface::class);

        /** @var TaxonAttributeValueInterface $attribute */
        foreach ($taxon->getAttributes() as $attribute) {
            if (null === $attribute->getValue()) {
                $taxon->removeAttribute($attribute);
            }
        }
    }

    private function resolveLocalizedAttributes(
        TaxonInterface $taxon,
        TaxonAttributeValueInterface $taxonAttributeValue,
    ): void {
        Assert::isInstanceOf($taxon, TaxonAttributeSubjectInterface::class);
        Assert::isInstanceOf($taxonAttributeValue->getAttribute(), TaxonAttributeInterface::class);

        $localeCodes = $this->localeProvider->getDefinedLocalesCodes();

        foreach ($localeCodes as $localeCode) {
            Assert::string($taxonAttributeValue->getCode());

            if (!$taxon->hasAttributeByCodeAndLocale($taxonAttributeValue->getCode(), $localeCode)) {
                $attributeValue = $this->createProductAttributeValue($taxonAttributeValue->getAttribute(), $localeCode);
                $taxon->addAttribute($attributeValue);
            }
        }
    }

    private function createProductAttributeValue(
        TaxonAttributeInterface $attribute,
        string $localeCode,
    ): TaxonAttributeValueInterface {
        /** @var TaxonAttributeValueInterface $attributeValue */
        $attributeValue = $this->taxonAttributeValueFactory->createNew();
        $attributeValue->setAttribute($attribute);
        $attributeValue->setLocaleCode($localeCode);

        return $attributeValue;
    }
}
