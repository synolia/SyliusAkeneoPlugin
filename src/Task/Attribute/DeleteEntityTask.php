<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttribute;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteEntityTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository */
    private $productAttributeAkeneoRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductAttributeRepository $productAttributeAkeneoRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeAkeneoRepository = $productAttributeAkeneoRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        $attributeCodes = [];

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                $attributeCodes[] = $resource['code'];
            }

            $this->removeUnusedAttributes($attributeCodes);

            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();

            throw $throwable;
        }

        return $payload;
    }

    private function removeUnusedAttributes(array $attributeCodes): void
    {
        /** @var array $attributesIdsArray */
        $attributesIdsArray = $this->productAttributeAkeneoRepository->getMissingAttributesIds($attributeCodes);

        /** @var array $attributesIds */
        $attributesIds = \array_map(function (array $data) {
            return $data['id'];
        }, $attributesIdsArray);

        foreach ($attributesIds as $attributeId) {
            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->entityManager->getReference(ProductAttribute::class, $attributeId);
            if (!$attribute instanceof AttributeInterface) {
                continue;
            }
            $this->entityManager->remove($attribute);
        }
    }
}
