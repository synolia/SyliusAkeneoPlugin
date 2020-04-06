<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantTranslation;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateConfigurableProductEntitiesTask extends AbstractCreateProductEntities implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionValueRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productVariantRepository,
        RepositoryInterface $productRepository,
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $channelRepository,
        RepositoryInterface $channelPricingRepository,
        RepositoryInterface $localeRepository,
        FactoryInterface $productVariantFactory,
        FactoryInterface $channelPricingFactory
    ) {
        parent::__construct(
            $entityManager,
            $productVariantRepository,
            $productRepository,
            $channelRepository,
            $channelPricingRepository,
            $localeRepository,
            $productVariantFactory,
            $channelPricingFactory
        );

        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        foreach ($payload->getConfigurableProductPayload()->getProducts() as $configurableProductItem) {
            try {
                $this->entityManager->beginTransaction();

                /** @var ProductInterface $productModel */
                $productModel = $this->productRepository->findOneBy(['code' => $configurableProductItem['parent']]);

                //Skip product variant import if it does not have a parent model on Sylius
                if (!$productModel instanceof ProductInterface) {
                    continue;
                }

                //Use fake variation axe "size" for testing purpose
                $variationAxes = ['size'];

                foreach ($configurableProductItem['values'] as $attributeCode => $values) {
                    /*
                     * Skip attributes that aren't variation axes.
                     * Variation axes value will be created as option for the product
                     */
                    if (!in_array($attributeCode, $variationAxes, true)) {
                        continue;
                    }

                    /** @var ProductOptionInterface $productOption */
                    $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);

                    //We cannot create the variant if the option does not exists
                    if (!$productOption instanceof ProductOptionInterface) {
                        continue;
                    }

                    if ($productModel->hasOption($productOption)) {
                        $productModel->addOption($productOption);
                    }

                    $productVariant = $this->productVariantRepository->findOneBy(['code' => $configurableProductItem['identifier']]);

                    if (!$productVariant instanceof ProductVariantInterface) {
                        /** @var ProductVariantInterface $productVariant */
                        $productVariant = $this->productVariantFactory->createForProduct($productModel);
                        $productVariant->setCode($configurableProductItem['identifier']);

                        $this->entityManager->persist($productVariant);
                    }

                    $this->setProductOptionValues($productVariant, $productOption, $values);
                    $this->setProductPrices($productVariant);
                }
                $this->entityManager->commit();
            } catch (\Throwable $throwable) {
                $this->entityManager->rollback();
            }

            $this->entityManager->flush();
        }

        return $payload;
    }

    private function getLocales(): iterable
    {
        /** @var LocaleInterface[] $locales */
        $locales = $this->localeRepository->findAll();

        foreach ($locales as $locale) {
            yield $locale->getCode();
        }
    }

    private function setProductOptionValues(
        ProductVariantInterface $productVariant,
        ProductOptionInterface $productOption,
        array $values
    ): void {
        foreach ($values as $optionValue) {
            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'option' => $productOption,
                'code' => $optionValue,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                continue;
            }

            //Product variant already have this value
            if (!$productVariant->hasOptionValue($productOptionValue)) {
                $productVariant->addOptionValue($productOptionValue);
            }

            foreach ($this->getLocales() as $locale) {
                /** @var \Sylius\Component\Product\Model\ProductOptionValueTranslationInterface $productOptionValueTranslation */
                $productOptionValueTranslation = $productOptionValue->getTranslation($locale);

                if (!$productOptionValueTranslation instanceof ProductOptionValueTranslationInterface) {
                    continue;
                }

                $productVariantTranslation = $productVariant->getTranslation($locale);

                if (!$productVariantTranslation instanceof ProductVariantTranslationInterface) {
                    $productVariantTranslation = new ProductVariantTranslation();
                    $productVariantTranslation->setLocale($locale);
                    $productVariant->addTranslation($productVariantTranslation);
                }

                $productVariantTranslation->setName($productOptionValueTranslation->getValue());
            }
        }
    }
}
