<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\AttributeType\SelectAttributeType;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AttributeOptionValueDataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;

final class SelectOptionValuesProcessor extends AbstractOptionValuesProcessor
{
    private AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer;

    public function __construct(
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $productOptionValueTranslationRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager,
        ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
        AttributeOptionValueDataTransformerInterface $attributeOptionValueDataTransformer
    ) {
        parent::__construct($productOptionValueRepository, $productOptionValueTranslationRepository, $productOptionValueFactory, $productOptionValueTranslationFactory, $akeneoLogger, $entityManager, $productOptionValueDataTransformer);
        $this->attributeOptionValueDataTransformer = $attributeOptionValueDataTransformer;
    }

    public function support(AttributeInterface $attribute, ProductOptionInterface $productOption, array $context = []): bool
    {
        return SelectAttributeType::TYPE === $attribute->getType();
    }

    public static function getDefaultPriority(): int
    {
        return 100;
    }

    public function process(AttributeInterface $attribute, ProductOptionInterface $productOption, array $context = []): void
    {
        $productOptionValuesMapping = [];
        $productOptionValueCodes = array_keys($attribute->getConfiguration()['choices']);
        foreach ($productOptionValueCodes as $productOptionValueCode) {
            if (isset($productOptionValuesMapping[(string) $productOptionValueCode])) {
                continue;
            }

            $transformedCode = $this->attributeOptionValueDataTransformer->reverseTransform((string) $productOptionValueCode);
            $transformedCode = $this->productOptionValueDataTransformer->transform($productOption, $transformedCode);

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'code' => $transformedCode,
                'option' => $productOption,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                /** @var ProductOptionValueInterface $productOptionValue */
                $productOptionValue = $this->productOptionValueFactory->createNew();
                $productOptionValue->setCode($transformedCode);
                $productOption->addValue($productOptionValue);

                $this->entityManager->persist($productOptionValue);
            }

            $this->updateProductOptionValueTranslations($productOptionValue, $attribute, (string) $productOptionValueCode);

            $productOptionValuesMapping[(string) $productOptionValueCode] = [
                'entity' => $productOptionValue,
                'translations' => $attribute->getConfiguration()['choices'][$productOptionValueCode],
            ];
        }
        $this->entityManager->flush();
    }

    private function updateProductOptionValueTranslations(
        ProductOptionValueInterface $productOptionValue,
        AttributeInterface $attribute,
        string $productOptionValueCode
    ): void {
        $translations = $attribute->getConfiguration()['choices'][$productOptionValueCode];

        foreach ($translations as $locale => $translation) {
            if (null === $translation) {
                $translation = sprintf('[%s]', $productOptionValueCode);
                $this->akeneoLogger->warning(sprintf(
                    'Missing translation on choice "%s" for option %s, defaulted to "%s"',
                    $productOptionValueCode,
                    $attribute->getCode(),
                    $translation,
                ));
            }

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
