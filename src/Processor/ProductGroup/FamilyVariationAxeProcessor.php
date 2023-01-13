<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductGroup;

use Akeneo\Pim\ApiClient\AkeneoPimClientInterface;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroupInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;

final class FamilyVariationAxeProcessor
{
    public int $itemCount = 0;

    private array $familyVariants;

    public function __construct(
        private AkeneoPimClientInterface $akeneoPimEnterpriseClient,
        private EntityRepository $productGroupRepository,
        private FamilyRetrieverInterface $familyRetriever,
        private LoggerInterface $logger,
    ) {
        $this->familyVariants = [];
    }

    public function process(array $resource): void
    {
        $productGroup = $this->productGroupRepository->findOneBy(['model' => $resource['code']]);
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
                $resource['family_variant'],
            );

            $this->familyVariants[$family][$resource['family_variant']] = $payloadProductGroup;
        }

        $productGroup->setVariationAxes([]);
        $productGroup->setAssociations($resource['associations']);
        $this->addAxes($this->familyVariants[$family], $family, $resource, $productGroup);
    }

    private function addAxes(
        array $familiesVariantPayloads,
        ?string $family,
        array $resource,
        ProductGroupInterface $productGroup,
    ): void {
        foreach ($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets'] as $variantAttributeSet) {
            if ((is_countable($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets']) ? \count($familiesVariantPayloads[$resource['family_variant']]['variant_attribute_sets']) : 0) !== $variantAttributeSet['level']) {
                continue;
            }

            foreach ($variantAttributeSet['axes'] as $axe) {
                $productGroup->addVariationAxe($axe);
                ++$this->itemCount;
                $this->logger->info(sprintf(
                    'Added axe "%s" to product group "%s" for family "%s"',
                    $axe,
                    $productGroup->getModel(),
                    $family ?: $resource['family'],
                ));
            }
        }
    }
}
