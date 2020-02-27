<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;

final class ProductOptionManager
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

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $localeRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionFactory
    ) {
        $this->entityManager = $entityManager;
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->localeRepository = $localeRepository;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionValueFactory = $productOptionValueFactory;
    }

    public function createOrUpdateProductOptionFromAttribute(AttributeInterface $attribute): ProductOptionInterface
    {
        $productOption = $this->productOptionRepository->findOneBy(['code' => $attribute->getCode()]);

        if (!$productOption instanceof ProductOptionInterface) {
            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionFactory->createNew();
            $productOption->setCode($attribute->getCode());
            $this->entityManager->persist($productOption);
        }

        $this->updateTranslationsFromAttribute($productOption, $attribute);
        $this->updateProductOptionValues($productOption, $attribute);

        return $productOption;
    }

    private function updateTranslationsFromAttribute(ProductOptionInterface $productOption, AttributeInterface $attribute): void
    {
        foreach ($this->getLocales() as $localeCode) {
            /** @var AttributeTranslationInterface $attributeTranslation */
            $attributeTranslation = $attribute->getTranslation($localeCode);
            //Skip unavailable translations
            if (!$attributeTranslation instanceof AttributeTranslationInterface) {
                continue;
            }

            $productOption->setCurrentLocale($localeCode);
            $productOption->setFallbackLocale($localeCode);
            $productOption->setName($attributeTranslation->getName());
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

    private function updateProductOptionValues(?ProductOptionInterface $productOption, AttributeInterface $attribute): void
    {
        foreach ($attribute->getConfiguration()['choices'] as $productOptionValueCode => $translations) {
            if (!is_string($productOptionValueCode)) {
                continue;
            }

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'code' => $productOptionValueCode,
                'option' => $productOption,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                /** @var ProductOptionValueInterface $productOptionValue */
                $productOptionValue = $this->productOptionValueFactory->createNew();
                $productOptionValue->setCode($productOptionValueCode);
                $productOptionValue->setOption($productOption);
                $this->entityManager->persist($productOptionValue);
            }

            foreach ($translations as $locale => $value) {
                $productOptionValue->setCurrentLocale($locale);
                $productOptionValue->setValue($value);
            }
        }
    }
}
