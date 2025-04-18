<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Checker\Product;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Exceptions\Retriever\FamilyVariantNotFountException;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyVariantRetrieverInterface;

final class IsProductProcessableChecker implements IsProductProcessableCheckerInterface
{
    private const ONE_VARIATION_AXIS = 1;

    public function __construct(
        private LoggerInterface $akeneoLogger,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private FamilyVariantRetrieverInterface $familyVariantRetriever,
    ) {
    }

    public function check(array $resource): bool
    {
        try {
            if ('' === $resource['code'] || null === $resource['code']) {
                $this->akeneoLogger->debug('Skipping product import because the code is missing.', ['resource' => $resource]);

                return false;
            }

            if (!isset($resource['family'])) {
                $this->akeneoLogger->debug('Skipping product import because the family is missing.', ['resource' => $resource]);

                return false;
            }

            $familyVariantPayload = $this->familyVariantRetriever->getVariant((string) $resource['family'], (string) $resource['family_variant']);

            $numberOfVariationAxis = isset($familyVariantPayload['variant_attribute_sets']) ? \count($familyVariantPayload['variant_attribute_sets']) : 0;

            if (
                null === $resource['parent'] &&
                $numberOfVariationAxis > self::ONE_VARIATION_AXIS &&
                $this->apiConnectionProvider->get()->getAxeAsModel() === AkeneoAxesEnum::FIRST
            ) {
                $this->akeneoLogger->debug('Skipping product import because the parent is null and it has more than one variation axis.', ['resource' => $resource]);

                return false;
            }

            // The common model will not be imported. The first axe on akeneo will become the product on sylius and the next axe on akeneo will become an option for the product variant
            if (
                null !== $resource['parent'] &&
                $numberOfVariationAxis === 2 &&
                $this->apiConnectionProvider->get()->getAxeAsModel() !== AkeneoAxesEnum::FIRST
            ) {
                $this->akeneoLogger->debug('Skipping product import because the parent is null, and it has more than one variation axis.', ['resource' => $resource]);

                return false;
            }

            return true;
        } catch (FamilyVariantNotFountException) {
            return false;
        }
    }
}
