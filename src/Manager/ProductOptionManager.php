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
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Webmozart\Assert\Assert;

final class ProductOptionManager implements ProductOptionManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RepositoryInterface $productAttributeTranslationRepository,
        private RepositoryInterface $productOptionRepository,
        private RepositoryInterface $productOptionTranslationRepository,
        private FactoryInterface $productOptionTranslationFactory,
        private FactoryInterface $productOptionFactory,
        private OptionValuesProcessorProviderInterface $optionValuesProcessorProvider,
        private LoggerInterface $akeneoLogger,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    public function getProductOptionFromAttribute(AttributeInterface $attribute): ?ProductOptionInterface
    {
        $productOption = $this->productOptionRepository->findOneBy(['code' => $attribute->getCode()]);
        Assert::nullOrIsInstanceOf($productOption, ProductOptionInterface::class);

        return $productOption;
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

    private function updateTranslationsFromAttribute(
        ProductOptionInterface $productOption,
        AttributeInterface $attribute,
    ): void {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $localeCode) {
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

    private function updateProductOptionValues(
        ProductOptionInterface $productOption,
        AttributeInterface $attribute,
    ): void {
        try {
            $processor = $this->optionValuesProcessorProvider->getProcessor($attribute, $productOption);
            $processor->process($attribute, $productOption);
        } catch (MissingProductOptionValuesProcessorException $missingProductOptionValuesProcessorException) {
            $this->akeneoLogger->debug($missingProductOptionValuesProcessorException->getMessage());
        }
    }
}
