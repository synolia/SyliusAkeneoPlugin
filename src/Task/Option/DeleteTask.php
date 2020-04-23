<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Option;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Product\Model\ProductOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
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

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $type;

    /** @var int */
    private $deleteCount = 0;

    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    private $parameterBag;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductAttributeRepository $productAttributeAkeneoRepository,
        ProductOptionRepository $productOptionAkeneoRepository,
        LoggerInterface $akeneoLogger,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeAkeneoRepository;
        $this->productOptionRepository = $productOptionAkeneoRepository;
        $this->logger = $akeneoLogger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::removalNoLongerExist($payload->getType()));
        $this->type = $payload->getType();

        if (!$this->productAttributeRepository instanceof ProductAttributeRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }
        if (!$this->productOptionRepository instanceof ProductOptionRepository) {
            throw new \LogicException('Wrong repository instance provided.');
        }

        try {
            $this->entityManager->beginTransaction();

            $this->process();

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countOfDeleted($payload->getType(), $this->deleteCount));

        return $payload;
    }

    private function process(): void
    {
        $attributeCodes = $this->productAttributeRepository->getAllAttributeCodes();
        $removedOptionIds = $this->productOptionRepository->getRemovedOptionIds($attributeCodes);

        $productOptionClass = $this->parameterBag->get('sylius.model.product_option.class');
        if (!class_exists($productOptionClass)) {
            throw new \LogicException('ProductOption class does not exists.');
        }

        foreach ($removedOptionIds as $removedOptionId) {
            /** @var ProductOption $referenceEntity */
            $referenceEntity = $this->entityManager->getReference($productOptionClass, $removedOptionId);
            if (null !== $referenceEntity) {
                $this->entityManager->remove($referenceEntity);
                $this->logger->info(Messages::hasBeenDeleted($this->type, (string) $referenceEntity->getCode()));
                ++$this->deleteCount;
            }
        }
    }
}
