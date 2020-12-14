<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class LocaleAttributeTranslationTask implements AkeneoTaskInterface
{
    private RepositoryInterface $productAttributeValueRepository;

    private FactoryInterface $productAttributeValueFactory;

    private AkeneoAttributeDataProvider $akeneoAttributeDataProvider;

    private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        RepositoryInterface $productAttributeValueRepository,
        FactoryInterface $productAttributeValueFactory,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof LocaleAttributeTranslationPayload) {
            return $payload;
        }

        $this->setAttributeTranslations(
            $payload,
            $payload->getAttribute(),
            $payload->getTranslations(),
            $payload->getTranslation(),
            $payload->getAttributeCode(),
            $payload->getScope()
        );

        return $payload;
    }

    private function setAttributeTranslations(
        LocaleAttributeTranslationPayload $payload,
        AttributeInterface $attribute,
        array $translations,
        array $translation,
        string $attributeCode,
        string $scope
    ): void {
        if ($translation['locale'] !== null && !$this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale'])) {
            return;
        }

        if ($translation['locale'] === null) {
            foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                $this->setAttributeTranslation($payload, $attribute, $translations, $locale, $attributeCode, $scope);
            }

            return;
        }

        $this->setAttributeTranslation($payload, $attribute, $translations, $translation['locale'], $attributeCode, $scope);
    }

    private function setAttributeTranslation(
        LocaleAttributeTranslationPayload $payload,
        AttributeInterface $attribute,
        array $translations,
        string $locale,
        string $attributeCode,
        string $scope
    ): void {
        $attributeValue = $this->productAttributeValueRepository->findOneBy([
            'subject' => $payload->getProduct(),
            'attribute' => $attribute,
            'localeCode' => $locale,
        ]);

        if (!$attributeValue instanceof ProductAttributeValueInterface) {
            /** @var \Sylius\Component\Product\Model\ProductAttributeValueInterface $attributeValue */
            $attributeValue = $this->productAttributeValueFactory->createNew();
        }

        $attributeValue->setLocaleCode($locale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoAttributeDataProvider->getData($attributeCode, $translations, $locale, $scope);
        $attributeValue->setValue($attributeValueValue);
        $payload->getProduct()->addAttribute($attributeValue);
    }
}
