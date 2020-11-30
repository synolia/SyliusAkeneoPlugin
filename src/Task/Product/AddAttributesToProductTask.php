<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\LocaleAttributeTranslationTask;
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

    public function __construct(
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        AkeneoTaskProvider $taskProvider
    ) {
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->taskProvider = $taskProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || $payload->getProduct() === null) {
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

            $this->setAttributeTranslations($payload, $translations, $attribute, (string) $attributeCode, $payload->getScope());
        }

        return $payload;
    }

    private function setAttributeTranslations(
        ProductResourcePayload $payload,
        array $translations,
        AttributeInterface $attribute,
        string $attributeCode,
        string $scope
    ): void {
        foreach ($translations as $translation) {
            if (!$payload->getProduct() instanceof ProductInterface) {
                return;
            }

            $localeAttributeTranslationPayload = new LocaleAttributeTranslationPayload($payload->getAkeneoPimClient());
            $localeAttributeTranslationPayload
                ->setProduct($payload->getProduct())
                ->setAttribute($attribute)
                ->setTranslations($translations)
                ->setTranslation($translation)
                ->setAttributeCode($attributeCode)
                ->setScope($scope);
            $localeAttributeTranslationTask = $this->taskProvider->get(LocaleAttributeTranslationTask::class);
            $localeAttributeTranslationTask->__invoke($localeAttributeTranslationPayload);
        }
    }
}
