<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Processor\MissingProductOptionValuesProcessorException;
use Synolia\SyliusAkeneoPlugin\Provider\OptionValuesProcessorProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;

final class ProductOptionManager implements ProductOptionManagerInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface */
    private $localeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productOptionTranslationFactory;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeTranslationRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\OptionValuesProcessorProviderInterface */
    private $optionValuesProcessorProvider;

    /** @var \Psr\Log\LoggerInterface */
    private $akeneoLogger;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeTranslationRepository,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionTranslationRepository,
        LocaleRepositoryInterface $localeRepository,
        FactoryInterface $productOptionTranslationFactory,
        FactoryInterface $productOptionFactory,
        OptionValuesProcessorProviderInterface $optionValuesProcessorProvider,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productOptionRepository = $productOptionRepository;
        $this->localeRepository = $localeRepository;
        $this->productOptionFactory = $productOptionFactory;
        $this->productOptionTranslationRepository = $productOptionTranslationRepository;
        $this->productOptionTranslationFactory = $productOptionTranslationFactory;
        $this->productAttributeTranslationRepository = $productAttributeTranslationRepository;
        $this->optionValuesProcessorProvider = $optionValuesProcessorProvider;
        $this->akeneoLogger = $akeneoLogger;
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
        foreach ($this->localeRepository->getLocaleCodes() as $localeCode) {
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

    private function updateProductOptionValues(ProductOptionInterface $productOption, AttributeInterface $attribute): void
    {
        try {
            $processor = $this->optionValuesProcessorProvider->getProcessor($attribute, $productOption);
            $processor->process($attribute, $productOption);
        } catch (MissingProductOptionValuesProcessorException $missingProductOptionValuesProcessorException) {
            $this->akeneoLogger->debug($missingProductOptionValuesProcessorException->getMessage());
        }
    }
}
