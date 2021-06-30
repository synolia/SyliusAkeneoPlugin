<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductGroup;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetriever;

class FamilyVariationAxeProcessor
{
    /** @var AkeneoPimEnterpriseClientInterface */
    private $akeneoPimEnterpriseClient;

    /** @var EntityRepository */
    private $productGroupRepository;

    /** @var FamilyRetriever */
    private $familyRetriever;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    public $itemCount = 0;

    public function __construct(
        AkeneoPimEnterpriseClientInterface $akeneoPimEnterpriseClient,
        EntityRepository $productGroupRepository,
        FamilyRetriever $familyRetriever,
        LoggerInterface $akeneoLogger
    ) {
        $this->akeneoPimEnterpriseClient = $akeneoPimEnterpriseClient;
        $this->productGroupRepository = $productGroupRepository;
        $this->familyRetriever = $familyRetriever;
        $this->logger = $akeneoLogger;
    }

    public function process(array $resource, array $familiesVariantPayloads = []): array
    {
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]);
        if (!$productGroup instanceof ProductGroup) {
            return $familiesVariantPayloads;
        }

        $family = null;
        if (!isset($resource['family'])) {
            try {
                $family = $this->familyRetriever->getFamilyCodeByVariantCode($resource['family_variant']);
            } catch (\LogicException $exception) {
                $this->logger->warning($exception->getMessage());

                return $familiesVariantPayloads;
            }
        }

        if (!isset($familiesVariantPayloads[$family ?: $resource['family']][$resource['family_variant']])) {
            $payloadProductGroup = $this->akeneoPimEnterpriseClient->getFamilyVariantApi()->get(
                $family ?: $resource['family'],
                $resource['family_variant']
            );

            $familiesVariantPayloads[$family ?: $resource['family']][$resource['family_variant']] = $payloadProductGroup;
        }

        $this->addAxes($familiesVariantPayloads[$family ?: $resource['family']], $family, $resource, $productGroup);

        return $familiesVariantPayloads;
    }

    private function addAxes(
        array $familiesVariantPayloads,
        ?string $family,
        array $resource,
        ProductGroup $productGroup
    ): void {
        foreach ($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets'] as $variantAttributeSet) {
            if (count($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets']) !== $variantAttributeSet['level']) {
                continue;
            }

            foreach ($variantAttributeSet['axes'] as $axe) {
                $productGroup->addVariationAxe($axe);
                ++$this->itemCount;
                $this->logger->info(\sprintf(
                    'Added axe "%s" to product group "%s" for family "%s"',
                    $axe,
                    $productGroup->getProductParent(),
                    $family ?: $resource['family']
                ));
            }
        }
    }
}
