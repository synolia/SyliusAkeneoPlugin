<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class DeleteEntityTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository */
    private $productAttributeAkeneoRepository;

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
        LoggerInterface $akeneoLogger,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeAkeneoRepository = $productAttributeAkeneoRepository;
        $this->logger = $akeneoLogger;
        $this->parameterBag = $parameterBag;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload $payload
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->logger->notice(Messages::removalNoLongerExist($payload->getType()));
        $this->type = $payload->getType();

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
            $this->logger->notice(Messages::countOfDeleted($payload->getType(), $this->deleteCount));

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

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

        $productAttributeClass = $this->parameterBag->get('sylius.model.product_attribute.class');
        if (!class_exists($productAttributeClass)) {
            throw new \LogicException('ProductAttribute class does not exists.');
        }

        foreach ($attributesIds as $attributeId) {
            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->entityManager->getReference($productAttributeClass, $attributeId);
            if (!$attribute instanceof AttributeInterface) {
                continue;
            }
            $this->entityManager->remove($attribute);
            $this->logger->info(Messages::hasBeenDeleted($this->type, (string) $attribute->getCode()));
            ++$this->deleteCount;
        }
    }
}
