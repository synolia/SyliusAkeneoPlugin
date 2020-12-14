<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetriever;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Throwable;

final class AddFamilyVariationAxeTask implements AkeneoTaskInterface
{
    private EntityManagerInterface $entityManager;

    private EntityRepository $productGroupRepository;

    private LoggerInterface $logger;

    private int $itemCount = 0;

    private string $type = '';

    private FamilyRetriever $familyRetriever;

    public function __construct(
        EntityManagerInterface $entityManager,
        EntityRepository $productGroupRepository,
        FamilyRetriever $familyRetriever,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productGroupRepository = $productGroupRepository;
        $this->familyRetriever = $familyRetriever;
        $this->logger = $akeneoLogger;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = 'FamilyVariationAxe';
        $this->logger->notice(Messages::createOrUpdate($this->type));

        $this->addFamilyVariationAxe($payload);

        $this->logger->notice(Messages::countItems($this->type, $this->itemCount));

        return $payload;
    }

    private function addFamilyVariationAxe(ProductModelPayload $payload): void
    {
        if (!$payload->getModelResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        $familiesVariantPayloads = [];

        try {
            $this->entityManager->beginTransaction();
            foreach ($payload->getModelResources() as $resource) {
                $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]);
                if (!$productGroup instanceof ProductGroup) {
                    continue;
                }

                $family = null;
                if (!isset($resource['family'])) {
                    try {
                        $family = $this->familyRetriever->getFamilyCodeByVariantCode($resource['family_variant']);
                    } catch (LogicException $exception) {
                        $this->logger->warning($exception->getMessage());

                        continue;
                    }
                }

                if (!isset($familiesVariantPayloads[$family ?: $resource['family']][$resource['family_variant']])) {
                    $payloadProductGroup = $payload->getAkeneoPimClient()->getFamilyVariantApi()->get(
                        $family ?: $resource['family'],
                        $resource['family_variant']
                    );

                    $familiesVariantPayloads[$family ?: $resource['family']][$resource['family_variant']] = $payloadProductGroup;
                }

                $this->addAxes($familiesVariantPayloads[$family ?: $resource['family']], $family, $resource, $productGroup);
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }
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
