<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Throwable;

final class DeleteEntityTask implements AkeneoTaskInterface
{
    private EntityManagerInterface $entityManager;

    private ProductAttributeRepository $productAttributeAkeneoRepository;

    private LoggerInterface $logger;

    private string $type = '';

    private int $deleteCount = 0;

    private ParameterBagInterface $parameterBag;

    private AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductAttributeRepository $productAttributeAkeneoRepository,
        LoggerInterface $akeneoLogger,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        ParameterBagInterface $parameterBag
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeAkeneoRepository = $productAttributeAkeneoRepository;
        $this->logger = $akeneoLogger;
        $this->parameterBag = $parameterBag;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
    }

    /**
     * @param AttributePayload $payload
     *
     * @throws NoAttributeResourcesException
     * @throws Throwable
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
                $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform((string) $resource['code']);
                $attributeCodes[] = $code;
            }

            $this->removeUnusedAttributes($attributeCodes);
            $this->logger->notice(Messages::countOfDeleted($payload->getType(), $this->deleteCount));

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (Throwable $throwable) {
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
        $attributesIds = \array_map(fn (array $data) => $data['id'], $attributesIdsArray);

        $productAttributeClass = $this->parameterBag->get('sylius.model.product_attribute.class');
        if (!class_exists($productAttributeClass)) {
            throw new LogicException('ProductAttribute class does not exist.');
        }

        foreach ($attributesIds as $attributeId) {
            /** @var AttributeInterface $attribute */
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
