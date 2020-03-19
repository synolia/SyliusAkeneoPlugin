<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ProductModel;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\ProductGroup;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductModelResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ProductModel\ProductModelPayload;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class AddFamilyVariationAxeTask implements AkeneoTaskInterface
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var EntityRepository */
    private $productGroupRepository;

    public function __construct(EntityManagerInterface $entityManager, EntityRepository $productGroupRepository)
    {
        $this->entityManager = $entityManager;
        $this->productGroupRepository = $productGroupRepository;
    }

    /**
     * @param ProductModelPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoProductModelResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();
            foreach ($payload->getResources() as $resource) {
                if ($resource['parent'] !== null) {
                    continue;
                }

                $productGroup = $this->productGroupRepository->findOneBy(['productParent' => $resource['code']]);
                if (!$productGroup instanceof ProductGroup) {
                    continue;
                }

                $payloadProductGroup = $payload->getAkeneoPimClient()->getFamilyVariantApi()->get($resource['family'], $resource['family_variant']);

                foreach ($payloadProductGroup['variant_attribute_sets'] as $variantAttributeSet) {
                    if (count($payloadProductGroup['variant_attribute_sets']) !== $variantAttributeSet['level']) {
                        continue;
                    }
                    foreach ($variantAttributeSet['axes'] as $axe) {
                        $productGroup->addVariationAxe($axe);
                    }
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }
}
