<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductOptionValue;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductOptionInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityAttributeType;
use Synolia\SyliusAkeneoPlugin\Provider\Data\AkeneoAttributePropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;
use Webmozart\Assert\Assert;

final class ReferenceEntityOptionValuesProcessor extends AbstractOptionValuesProcessor
{
    public function __construct(
        RepositoryInterface $productOptionValueRepository,
        RepositoryInterface $productOptionValueTranslationRepository,
        FactoryInterface $productOptionValueFactory,
        FactoryInterface $productOptionValueTranslationFactory,
        LoggerInterface $akeneoLogger,
        EntityManagerInterface $entityManager,
        private AkeneoPimClientInterface $client,
        private AkeneoAttributePropertiesProviderInterface $akeneoAttributePropertiesProvider,
        ProductOptionValueDataTransformerInterface $productOptionValueDataTransformer,
        private LocaleRepositoryInterface $localeRepository,
        private EditionCheckerInterface $editionChecker,
    ) {
        parent::__construct(
            $productOptionValueRepository,
            $productOptionValueTranslationRepository,
            $productOptionValueFactory,
            $productOptionValueTranslationFactory,
            $akeneoLogger,
            $entityManager,
            $productOptionValueDataTransformer,
        );
    }

    public static function getDefaultPriority(): int
    {
        return 90;
    }

    public function support(
        AttributeInterface $attribute,
        ProductOptionInterface $productOption,
        array $context = [],
    ): bool {
        return
            ReferenceEntityAttributeType::TYPE === $attribute->getType() &&
            ($this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition());
    }

    public function process(
        AttributeInterface $attribute,
        ProductOptionInterface $productOption,
        array $context = [],
    ): void {
        Assert::string($attribute->getCode());

        $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attribute->getCode());
        $records = $this->client->getReferenceEntityRecordApi()->all($referenceEntityAttributeProperties['reference_data_name']);

        foreach ($records as $record) {
            $optionValueCode = $this->productOptionValueDataTransformer->transform($productOption, $record['code']);

            $productOptionValue = $this->productOptionValueRepository->findOneBy([
                'code' => $optionValueCode,
                'option' => $productOption,
            ]);

            if (!$productOptionValue instanceof ProductOptionValueInterface) {
                /** @var ProductOptionValueInterface $productOptionValue */
                $productOptionValue = $this->productOptionValueFactory->createNew();
                $productOptionValue->setCode($optionValueCode);
                $productOptionValue->setOption($productOption);
                $this->entityManager->persist($productOptionValue);
            }

            $this->updateProductOptionValueTranslations($productOptionValue, $attribute, $record);

            $this->entityManager->flush();
        }
    }

    private function updateProductOptionValueTranslations(
        ProductOptionValueInterface $productOptionValue,
        AttributeInterface $attribute,
        array $record,
    ): void {
        //Seems not to be customizable on Akeneo, but can be removed
        $translations = $record['values']['label'] ?? [];

        if (0 === (is_countable($translations) ? \count($translations) : 0)) {
            foreach ($this->localeRepository->getLocaleCodes() as $locale) {
                $translations[] = [
                    'locale' => $locale,
                    'data' => sprintf('[%s]', $record['code']),
                ];
            }
        }

        foreach ($translations as  $translation) {
            $locale = $translation['locale'];

            if (null === $translation['data']) {
                $translation = sprintf('[%s]', $record['code']);
                $this->akeneoLogger->warning(sprintf(
                    'Missing translation on choice "%s" for option %s, defaulted to "%s"',
                    $record['code'],
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

            $productOptionValueTranslation->setValue($translation['data']);
        }
    }
}
