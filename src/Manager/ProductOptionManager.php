<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class ProductOptionManager implements ProductOptionManagerInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $localeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionValueFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionValueTranslationFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionTranslationFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeTranslationRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeTranslationRepository,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $productOptionValueTranslationRepository,
        RepositoryInterface $productOptionTranslationRepository,
        RepositoryInterface $localeRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        FactoryInterface $productOptionTranslationFactory,
        FactoryInterface $productOptionFactory
    ) {
        $this->entityManager = $entityManager;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->localeRepository = $localeRepository;
        $this->productOptionValueTranslationRepository = $productOptionValueTranslationRepository;
        $this->productOptionValueFactory = $productOptionValueFactory;
        $this->productOptionValueTranslationFactory = $productOptionValueTranslationFactory;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionTranslationRepository = $productOptionTranslationRepository;
        $this->productOptionTranslationFactory = $productOptionTranslationFactory;
        $this->productAttributeTranslationRepository = $productAttributeTranslationRepository;
    }

    public function getProductOptionFromAttribute(AttributeInterface $attribute): ?ProductOptionInterface
    {
        return $this->productOptionRepository->findOneBy(['code' => $attribute->getCode()]);
    }

    public function createProductOptionFromAttribute(AttributeInterface $attribute): ProductOptionInterface
    {
        /** @var ProductOptionInterface $productOption */
        $productOption = $this->productOptionFactory->createNew();
        $productOption->setCode($attribute->getCode());
        $this->entityManager->persist($productOption);

        return $productOption;
    }

    public function updateData(AttributeInterface $attribute, ProductOptionInterface $productOption): void
    {
        $this->updateTranslationsFromAttribute($productOption, $attribute);
        $this->updateProductOptionValues($productOption, $attribute);
    }

    public static function getOptionValueCodeFromProductOption(
        ProductOptionInterface $productOption,
        string $optionValueCode
    ): string {
        return \strtolower(\sprintf('%s_%s', (string) $productOption->getCode(), $optionValueCode));
    }

    private function updateTranslationsFromAttribute(ProductOptionInterface $productOption, AttributeInterface $attribute): void
    {
        foreach ($this->getLocales() as $localeCode) {
            /** @var AttributeTranslationInterface|null $attributeTranslation */
            $attributeTranslation = $this->productAttributeTranslationRepository->findOneBy([
                'translatable' => $attribute,
                'locale' => $localeCode,
            ]);

            //Skip unavailable translations
            if (!$attributeTranslation instanceof AttributeTranslationInterface) {
                continue;
            }

            $productOptionTranslation = $this->productOptionTranslationRepository->findOneBy([
                'locale' => $localeCode,
                'translatable' => $productOption,
            ]);

            if (!$productOptionTranslation instanceof ProductOptionTranslationInterface) {
                /** @var ProductOptionTranslationInterface $productOptionTranslation */
                $productOptionTranslation = $this->productOptionTranslationFactory->createNew();
                $productOptionTranslation->setTranslatable($productOption);
                $productOptionTranslation->setLocale($localeCode);
                $this->entityManager->persist($productOptionTranslation);
            }

            $productOptionTranslation->setName($attributeTranslation->getName());
        }
    }

    private function getLocales(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();
        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }

    private function updateProductOptionValues(ProductOptionInterface $productOption, AttributeInterface $attribute): void
    {
        if ($attribute->getType() !== SelectAttributeType::TYPE) {
            return;
        }

        $productOptionValuesMapping = [];
        $productOptionValueCodes = \array_keys($attribute->getConfiguration()['choices']);
        foreach ($productOptionValueCodes as $productOptionValueCode) {
            if (isset($productOptionValuesMapping[(string) $productOptionValueCode])) {
                continue;
            }

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'code' => self::getOptionValueCodeFromProductOption($productOption, (string) $productOptionValueCode),
                'option' => $productOption,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                /** @var ProductOptionValueInterface $productOptionValue */
                $productOptionValue = $this->productOptionValueFactory->createNew();
                $productOptionValue->setCode(self::getOptionValueCodeFromProductOption($productOption, (string) $productOptionValueCode));
                $productOptionValue->setOption($productOption);
                $this->entityManager->persist($productOptionValue);
            }

            $this->updateProductOptionValueTranslations($productOptionValue, $attribute, (string) $productOptionValueCode);

            $productOptionValuesMapping[(string) $productOptionValueCode] = [
                'entity' => $productOptionValue,
                'translations' => $attribute->getConfiguration()['choices'][$productOptionValueCode],
            ];
        }
    }

    private function updateProductOptionValueTranslations(
        ProductOptionValueInterface $productOptionValue,
        AttributeInterface $attribute,
        string $productOptionValueCode
    ): void {
        $translations = $attribute->getConfiguration()['choices'][$productOptionValueCode];

        foreach ($translations as $locale => $translation) {
            $productOptionValueTranslation = $this->productOptionValueTranslationRepository->findOneBy([
                'locale' => $locale,
                'translatable' => $productOptionValue,
            ]);

            if (!$productOptionValueTranslation instanceof ProductOptionValueTranslationInterface) {
                /** @var ProductOptionValueTranslationInterface $productOptionValueTranslation */
                $productOptionValueTranslation = $this->productOptionValueTranslationFactory->createNew();
                $productOptionValueTranslation->setTranslatable($productOptionValue);
                $productOptionValueTranslation->setLocale($locale);

                $this->entityManager->persist($productOptionValueTranslation);
            }

            $productOptionValueTranslation->setValue($translation);
        }
    }
}
