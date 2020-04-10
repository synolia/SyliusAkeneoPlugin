<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Product\Model\ProductOption;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductOptionRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository */
    private $productAttributeRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductOptionRepository */
    private $productOptionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductAttributeRepository $productAttributeAkeneoRepository,
        ProductOptionRepository $productOptionAkeneoRepository
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeAkeneoRepository;
        $this->productOptionRepository = $productOptionAkeneoRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$this->productAttributeRepository instanceof ProductAttributeRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }
        if (!$this->productOptionRepository instanceof ProductOptionRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }

        try {
            $this->entityManager->beginTransaction();

            $attributeCodes = $this->productAttributeRepository->getAllAttributeCodes();
            $removedOptionIds = $this->productOptionRepository->getRemovedOptionIds($attributeCodes);

            foreach ($removedOptionIds as $removedOptionId) {
                $referenceEntity = $this->entityManager->getReference(ProductOption::class, $removedOptionId);
                if (null !== $referenceEntity) {
                    $this->entityManager->remove($referenceEntity);
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
