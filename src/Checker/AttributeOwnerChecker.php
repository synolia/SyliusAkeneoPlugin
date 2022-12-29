<?php

namespace Synolia\SyliusAkeneoPlugin\Checker;

use Synolia\SyliusAkeneoPlugin\Config\AkeneoAxesEnum;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyVariantRetriever;

class AttributeOwnerChecker
{
    private const ONE_VARIATION_AXIS = 1;

    private FamilyVariantRetriever $familyVariantRetriever;
    private FamilyRetrieverInterface $familyRetriever;
    private ProductFilterRulesProviderInterface $productFilterRulesProvider;

    private ?string $scope = null;
    private ApiConnectionProviderInterface $apiConnectionProvider;

    public function __construct(
        FamilyVariantRetriever $familyVariantRetriever,
        FamilyRetrieverInterface $familyRetriever,
        ProductFilterRulesProviderInterface $productFilterRulesProvider,
        ApiConnectionProviderInterface $apiConnectionProvider
    ) {
        $this->familyVariantRetriever = $familyVariantRetriever;
        $this->familyRetriever = $familyRetriever;
        $this->productFilterRulesProvider = $productFilterRulesProvider;
        $this->apiConnectionProvider = $apiConnectionProvider;
    }

    public function isAttributePartOfModel(array $resource, string $attributeCode): bool
    {
        $apiConnection = $this->apiConnectionProvider->get();

        //TODO: create a configuration to allow import only attribute required by scope "ecommerce" or to allow all attributes

        // Only get attributes required on scope
        $familyAttributes = $this->getFamilyAttributeRequiredByScope($resource['family']);

        if (!array_key_exists('family_variant', $resource) && $this->isCommonProduct($resource)) {
            return in_array(
                $attributeCode,
                $familyAttributes,
                true
            );
        }

        $variant = $this->familyVariantRetriever->getVariant($resource['family'], $resource['family_variant']);

        // SI je suis un commun
        // ET que j'ai choisi d'importer le commun en tant que modèle
        // ALORS les attributs de mon modèle sont ceux listés sur la famille MOINS ceux listés dans tous les niveaux d'axes
        if (
            $this->isCommonProduct($resource) &&
            $apiConnection->getAxeAsModel() === AkeneoAxesEnum::COMMON
        ) {
            return in_array(
                $attributeCode,
                array_diff(
                    $familyAttributes,
                    $this->getFamilyVariantAttributeByLevel($variant, 1),
                    $this->getFamilyVariantAttributeByLevel($variant, 2),
                    $this->getFamilyVariantAttributeByLevel($variant, 3),
                ),
                true
            );
        }

        // SI mon parent n'est pas un commun
        // ET que j'ai choisi d'importer le premier axe en tant que produit modèle
        // ALORS les attributs de mon modèle sont ceux listés sur la famille MOINS ceux listés dans les axes de level supérieur à 1
        if (
            !$this->isCommonProduct($resource) &&
            $apiConnection->getAxeAsModel() === AkeneoAxesEnum::FIRST
        ) {
            return in_array(
                $attributeCode,
                array_diff(
                    $familyAttributes,
                    $this->getFamilyVariantAttributeByLevel($variant, 2),
                    $this->getFamilyVariantAttributeByLevel($variant, 3),
                ),
                true
            );
        }

        return false;
    }

    public function isAttributePartOfVariant(array $resource): bool
    {
        return false;
    }

    private function isCommonProduct(array $resource): bool
    {
        return $resource['parent'] === null;
    }

    private function getScope(): string
    {
        if (null !== $this->scope) {
            return $this->scope;
        }

        return $this->productFilterRulesProvider->getProductFiltersRules()->getChannel();
    }

    private function getFamilyAttributeRequiredByScope(string $familyCode): array
    {
        $family = $this->familyRetriever->getFamily($familyCode);

        return $family['attribute_requirements'][$this->getScope()];
    }

    private function getFamilyVariantAttributeByLevel(array $variant, int $level): array
    {
        foreach ($variant['variant_attribute_sets'] as $variantAttributeSet) {
            if ($variantAttributeSet['level'] !== $level) {
                continue;
            }

            return $variantAttributeSet['attributes'];
        }

        return [];
    }
}
