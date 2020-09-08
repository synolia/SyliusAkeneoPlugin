<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\AbstractAttributeOptionTask;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\MultipleOptionAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\SingleOptionAttributeTypeMatcher;

final class CreateUpdateReferenceEntityAttributeSubAttributeOptionsTaskTask extends AbstractAttributeOptionTask implements AkeneoTaskInterface
{
    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger
    ) {
        parent::__construct($entityManager, $akeneoLogger, $syliusAkeneoLocaleCodeProvider);

        $this->entityManager = $entityManager;
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
                foreach ($optionResources['sub_attributes'] as $attribute) {
                    if ($attribute['resource']['code'] === 'label' || null === $attribute['entity']) {
                        continue;
                    }

                    if (!in_array(\get_class($attribute['type_matcher']), [
                        SingleOptionAttributeTypeMatcher::class,
                        MultipleOptionAttributeTypeMatcher::class,
                    ], true)) {
                        continue;
                    }

                    $optionResources['isMultiple'] = $attribute['type_matcher'] instanceof MultipleOptionAttributeTypeMatcher;

                    $options = $payload->getAkeneoPimClient()->getReferenceEntityAttributeOptionApi()->all(
                        $optionResources['reference_data_name'],
                        $attribute['resource']['code']
                    );

                    $this->setAttributeChoices(
                        $attribute['entity'],
                        $options,
                        $optionResources['isMultiple']
                    );
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
