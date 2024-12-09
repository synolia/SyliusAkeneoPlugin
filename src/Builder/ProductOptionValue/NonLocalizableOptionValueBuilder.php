<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValue;

use Psr\Log\LoggerInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Event\ProductOptionValueTranslation\AfterProcessingProductOptionValueTranslationEvent;
use Synolia\SyliusAkeneoPlugin\Event\ProductOptionValueTranslation\BeforeProcessingProductOptionValueTranslationEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Builder\ProductOptionValueTranslation\ProductOptionValueTranslationBuilderNotFoundException;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;

class NonLocalizableOptionValueBuilder implements DynamicOptionValueBuilderInterface
{
    public static function getDefaultPriority(): int
    {
        return 100;
    }

    public function __construct(
        private FactoryInterface $productOptionValueFactory,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertiesProvider,
        private ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
        private ProductOptionValueTranslationBuilderProcessorInterface $productOptionValueTranslationBuilder,
        private LoggerInterface $akeneoLogger,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function support(ProductOptionInterface $productOption, mixed $values, array $context = []): bool
    {
        try {
            $attributeCode = $productOption->getCode();
            Assert::string($attributeCode);

            return
                !$this->akeneoAttributePropertiesProvider->isLocalizable($attributeCode) &&
                is_array($values) &&
                $values !== [] &&
                array_key_exists('data', $values[0]);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function build(
        ProductOptionInterface $productOption,
        mixed $values,
        array $context = [],
    ): ProductOptionValueInterface {
        Assert::isArray($values);

        /** @phpstan-ignore-next-line */
        $code = $this->getCode($productOption, $values[0]['data']);

        /** @var ProductOptionValueInterface $productOptionValue */
        $productOptionValue = $this->productOptionValueFactory->createNew();
        $productOptionValue->setCode($code);
        $productOptionValue->setOption($productOption);
        $productOption->addValue($productOptionValue);

        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
            $this->eventDispatcher->dispatch(new BeforeProcessingProductOptionValueTranslationEvent(
                $productOption,
                $productOptionValue,
                $locale,
                $values,
            ));

            try {
                $productOptionValueTranslation = $this->productOptionValueTranslationBuilder->build(
                    $productOption,
                    $productOptionValue,
                    $locale,
                    $values,
                );

                $this->eventDispatcher->dispatch(new AfterProcessingProductOptionValueTranslationEvent(
                    $productOption,
                    $productOptionValue,
                    $productOptionValueTranslation,
                    $locale,
                    $values,
                ));
            } catch (ProductOptionValueTranslationBuilderNotFoundException) {
                $this->akeneoLogger->warning('Could not create ProductOptionValueTranslation', [
                    'product_option' => $productOption->getCode(),
                    'product_option_value' => $productOptionValue->getCode(),
                    'locale' => $locale,
                    'attribute_values' => $values,
                ]);
            }
        }

        return $productOptionValue;
    }

    private function getCode(ProductOptionInterface $productOption, array|string $data): string
    {
        if (!\is_array($data)) {
            return $this->productOptionValueDataTransformer->transform($productOption, $data);
        }

        return $this->productOptionValueDataTransformer->transform($productOption, implode('_', $data));
    }
}
