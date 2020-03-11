<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Product\Model\ProductOption;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductOptionRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productOptionRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeAkeneoRepository,
        RepositoryInterface $productOptionAkeneoRepository
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
