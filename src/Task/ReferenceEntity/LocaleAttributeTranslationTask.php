<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;

final class LocaleAttributeTranslationTask implements AkeneoTaskInterface
{
    /** @var RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var FactoryInterface */
    private $productAttributeValueFactory;

    /** @var AkeneoReferenceEntityAttributeDataProvider */
    private $akeneoReferenceEntityAttributeDataProvider;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        RepositoryInterface $productAttributeValueRepository,
        FactoryInterface $productAttributeValueFactory,
        AkeneoReferenceEntityAttributeDataProvider $akeneoReferenceEntityAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider
    ) {
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->akeneoReferenceEntityAttributeDataProvider = $akeneoReferenceEntityAttributeDataProvider;
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
        if ($translation['locale'] !== null && $this->syliusAkeneoLocaleCodeProvider->isActiveLocale($translation['locale']) === false) {
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

        $localeValues = $this->getLocaleTranslations($attribute, $translations, $locale);
        if ($localeValues === null) {
            return;
        }

        $attributeValue->setLocaleCode($locale);
        $attributeValue->setAttribute($attribute);
        $attributeValueValue = $this->akeneoReferenceEntityAttributeDataProvider->getData(
            $payload->getReferenceEntityCode(),
            $payload->getAttributeCode(),
            $localeValues,
            $locale,
            $scope
        );
        $attributeValue->setValue($attributeValueValue);
        $payload->getProduct()->addAttribute($attributeValue);
    }

    private function getLocaleTranslations(AttributeInterface $attribute, array $translations, string $locale): ?array
    {
        $localeTranslations = [];
        if (count($translations) > 1) {
            foreach ($translations as $translation) {
                if ($this->syliusAkeneoLocaleCodeProvider->isLocaleDataTranslation($attribute, $translation, $locale) === false) {
                    continue;
                }
                $localeTranslations[] = $translation;
            }

            return $localeTranslations;
        }

        if ($attribute->getConfiguration() === []) {
            return $translations;
        }

        if (!is_array($translations[0]['data'])) {
            $isLocaleDataValues = $this->syliusAkeneoLocaleCodeProvider->isLocaleDataTranslation($attribute, CreateUpdateDeleteTask::AKENEO_PREFIX . $translations[0]['data'], $locale);
            if ($isLocaleDataValues === false) {
                return null;
            }

            return $translations;
        }

        $datas = [];
        foreach ($translations[0]['data'] as $data) {
            $data = CreateUpdateDeleteTask::AKENEO_PREFIX . $data;
            if ($this->syliusAkeneoLocaleCodeProvider->isLocaleDataTranslation($attribute, $data, $locale) === false) {
                continue;
            }
            $datas[] = $data;
        }

        $translations[0]['data'] = $datas;

        return $translations;
    }
}
