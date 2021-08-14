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

    /** @var array */
    private $familyVariants;

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
        $this->familyVariants = [];
    }

    public function process(array $resource): void
    {
        $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]);
        if (!$productGroup instanceof ProductGroup) {
            return;
        }

        $family = null;
        if (!isset($resource['family'])) {
            try {
                $family = $this->familyRetriever->getFamilyCodeByVariantCode($resource['family_variant']);
            } catch (\LogicException $exception) {
                $this->logger->warning($exception->getMessage());

                return;
            }
        }

        $family = $family ?: $resource['family'];

        if (!isset($this->familyVariants[$family][$resource['family_variant']])) {
            $payloadProductGroup = $this->akeneoPimEnterpriseClient->getFamilyVariantApi()->get(
                $family,
                $resource['family_variant']
            );

            $this->familyVariants[$family][$resource['family_variant']] = $payloadProductGroup;
        }

        $this->addAxes($this->familyVariants[$family], $family, $resource, $productGroup);
    }

    private function addAxes(
        array $familiesVariantPayloads,
        ?string $family,
        array $resource,
        ProductGroup $productGroup
    ): void {
        foreach ($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets'] as $variantAttributeSet) {
            if (\count($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets']) !== $variantAttributeSet['level']) {
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
