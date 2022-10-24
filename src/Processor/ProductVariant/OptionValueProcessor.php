<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductGroupRepository;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;
use Webmozart\Assert\Assert;

class OptionValueProcessor implements OptionValueProcessorInterface
{
    private RepositoryInterface $productOptionRepository;

    private RepositoryInterface $productOptionValueRepository;

    private ProductGroupRepository $productGroupRepository;

    private ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer;

    private LoggerInterface $logger;

    public function __construct(
        RepositoryInterface $productOptionRepository,
        RepositoryInterface $productOptionValueRepository,
        ProductGroupRepository $productGroupRepository,
        ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
        LoggerInterface $logger
    ) {
        $this->productOptionRepository = $productOptionRepository;
        $this->productOptionValueRepository = $productOptionValueRepository;
        $this->productGroupRepository = $productGroupRepository;
        $this->productOptionValueDataTransformer = $productOptionValueDataTransformer;
        $this->logger = $logger;
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        $productModel = $productVariant->getProduct();
        Assert::isInstanceOf($productModel, ProductInterface::class);

        /** @var ProductGroupInterface $productGroup */
        $productGroup = $this->productGroupRepository->findOneBy(
            ['productParent' => $productModel->getCode()]
        );

        $variationAxes = $productGroup->getVariationAxes();

        foreach ($resource['values'] as $attributeCode => $values) {
            /*
             * Skip attributes that aren't variation axes.
             * Variation axes value will be created as option for the product
             */
            if (!\in_array($attributeCode, $variationAxes, true)) {
                continue;
            }

            /** @var ProductOptionInterface $productOption */
            $productOption = $this->productOptionRepository->findOneBy(['code' => $attributeCode]);

            //We cannot create the variant if the option does not exist
            if (!$productOption instanceof ProductOptionInterface) {
                $this->logger->warning(
                    sprintf(
                        'Skipped ProductVariant "%s" creation because ProductOption "%s" does not exist.',
                        $productVariant->getCode(),
                        $attributeCode
                    )
                );

                continue;
            }

            if (!$productModel->hasOption($productOption)) {
                $productModel->addOption($productOption);
            }

            $this->setProductOptionValues($productVariant, $productOption, $values);
        }
    }

    private function setProductOptionValues(
        ProductVariantInterface $productVariant,
        ProductOptionInterface $productOption,
        array $values
    ): void {
        foreach ($values as $optionValue) {
            $code = $this->getCode($productOption, $optionValue['data']);
            $value = $this->getValue($optionValue['data']);

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'option' => $productOption,
                'code' => $code,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                $this->logger->warning(sprintf(
                    'Skipped variant value %s for option %s on variant %s because ProductOptionValue does not exist.',
                    $value,
                    $productOption->getCode(),
                    $productVariant->getCode(),
                ));

                return;
            }

            //Product variant already have this value
            if (!$productVariant->hasOptionValue($productOptionValue)) {
                $productVariant->addOptionValue($productOptionValue);
            }
        }
    }

    /**
     * @param array|string $data
     */
    private function getCode(ProductOptionInterface $productOption, $data): string
    {
        if (!\is_array($data)) {
            return $this->productOptionValueDataTransformer->transform($productOption, $data);
        }

        return $this->productOptionValueDataTransformer->transform($productOption, implode('_', $data));
    }

    /**
     * @param array|string $data
     */
    private function getValue($data): string
    {
        if (!\is_array($data)) {
            return $data;
        }

        return implode(' ', $data);
    }

    public function support(ProductVariantInterface $productVariant, array $resource): bool
    {
        $productModel = $productVariant->getProduct();

        if (!$productModel instanceof ProductInterface) {
            return false;
        }

        $productGroup = $this->productGroupRepository->findOneBy(
            ['productParent' => $productModel->getCode()]
        );

        if (!$productGroup instanceof ProductGroup) {
            $this->logger->warning(
                sprintf(
                    'Skipped product "%s" because model "%s" does not exist as group.',
                    $resource['identifier'],
                    $resource['parent'],
                )
            );

            return false;
        }

        $variationAxes = $productGroup->getVariationAxes();

        if (0 === \count($variationAxes)) {
            $this->logger->warning(
                sprintf(
                    'Skipped product "%s" because group has no variation axis.',
                    $resource['identifier'],
                )
            );

            return false;
        }

        return true;
    }

    public static function getDefaultPriority(): int
    {
        return 900;
    }
}
