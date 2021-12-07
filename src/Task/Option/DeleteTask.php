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
    private EntityManagerInterface $entityManager;

    private ProductAttributeRepository $productAttributeRepository;

    private ProductOptionRepository $productOptionRepository;

    private LoggerInterface $logger;

    private string $type;

    private int $deleteCount = 0;

    private ParameterBagInterface $parameterBag;

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

        /** @var class-string $productOptionClass */
        $productOptionClass = $this->parameterBag->get('sylius.model.product_option.class');
        if (!class_exists($productOptionClass)) {
            throw new \LogicException('ProductOption class does not exist.');
        }

        foreach ($removedOptionIds as $removedOptionId) {
            /** @var ProductOption $referenceEntity */
            $referenceEntity = $this->entityManager->getReference($productOptionClass, $removedOptionId);
            if (null !== $referenceEntity) {
                foreach ($referenceEntity->getValues() as $optionValue) {
                    $this->entityManager->remove($optionValue);
                }
                $this->entityManager->remove($referenceEntity);
                $this->logger->info(Messages::hasBeenDeleted($this->type, (string) $referenceEntity->getCode()));
                ++$this->deleteCount;
            }
        }
    }
}
