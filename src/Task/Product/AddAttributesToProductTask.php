<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Product\Model\ProductAttributeValueInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AddAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var AkeneoTaskProvider */
    private $taskProvider;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeValueRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeValueFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProvider */
    private $akeneoAttributeDataProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        AkeneoTaskProvider $taskProvider,
        RepositoryInterface $productAttributeValueRepository,
        FactoryInterface $productAttributeValueFactory,
        AkeneoAttributeDataProvider $akeneoAttributeDataProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider
    ) {
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->taskProvider = $taskProvider;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->productAttributeValueFactory = $productAttributeValueFactory;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || !$payload->getProduct() instanceof ProductInterface) {
            return $payload;
        }

        foreach ($payload->getResource()['values'] as $attributeCode => $translations) {
            if ($payload->getProductNameAttribute() === $attributeCode) {
                continue;
            }
            $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform((string) $attributeCode);

            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);
            if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
                continue;
            }

            if (!$this->attributeValueValueBuilder->hasSupportedBuilder((string) $attributeCode)) {
                continue;
            }
            $this->process($payload, $translations, $attribute, (string) $attributeCode, $payload->getScope());
        }

        return $payload;
    }

    private function process(
        ProductResourcePayload $payload,
        array $translations,
        AttributeInterface $attribute,
        string $attributeCode,
        string $scope
    ): void {
        foreach ($translations as $translation) {
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
    }

    private function setAttributeTranslation(
        ProductResourcePayload $payload,
        AttributeInterface $attribute,
        array $translations,
        string $locale,
        string $attributeCode,
        string $scope
    ): void {
        if (!$payload->getProduct() instanceof ProductInterface) {
            return;
        }

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
