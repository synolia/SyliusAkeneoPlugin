<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\AbstractAttributeTask;

final class CreateUpdateReferenceEntityAttributeSubAttributesTask extends AbstractAttributeTask implements AkeneoTaskInterface
{
    public const AKENEO_PREFIX = 'akeneo-';

    public function __construct(
        EntityManagerInterface $entityManager,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        RepositoryInterface $productAttributeRepository,
        FactoryInterface $productAttributeFactory,
        LoggerInterface $akeneoLogger
    ) {
        parent::__construct(
            $entityManager,
            $syliusAkeneoLocaleCodeProvider,
            $productAttributeRepository,
            $productAttributeFactory,
            $akeneoLogger
        );

        $this->logger = $akeneoLogger;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload $payload
     *
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (null === $payload->getResources() || 0 === \count($payload->getResources())) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $referenceEntityAttributeCode => $optionResources) {
                foreach ($optionResources['sub_attributes'] as $subAttributeKey => $attribute) {
                    if ($attribute['resource']['code'] === 'label') {
                        continue;
                    }

                    $code = $optionResources['attribute']->getCode() . '_' . $attribute['resource']['code'];

                    $subAttribute = $this->getOrCreateEntity($code, $attribute['type_matcher']);
                    $this->setAttributeTranslations($attribute['resource']['labels'], $subAttribute);
                    $optionResources['sub_attributes'][$subAttributeKey]['entity'] = $subAttribute;
                    $payload->setResource($referenceEntityAttributeCode, $optionResources);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }
}
