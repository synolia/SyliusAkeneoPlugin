<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedReferenceEntityAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcher;

final class RetrieveReferenceEntityAttributesTask implements AkeneoTaskInterface
{
    public const AKENEO_PREFIX = 'akeneo-';

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcher */
    private $referenceEntityAttributeTypeMatcher;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        ReferenceEntityAttributeTypeMatcher $referenceEntityAttributeTypeMatcher,
        LoggerInterface $akeneoLogger
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->referenceEntityAttributeTypeMatcher = $referenceEntityAttributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
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

            foreach ($payload->getResources() as $attributeCode => $optionResources) {
                //Call the API
                $attributes = $payload->getAkeneoPimClient()->getReferenceEntityAttributeApi()->all($optionResources['reference_data_name']);
                $optionResources['sub_attributes'] = [];

                foreach ($attributes as $attribute) {
                    try {
                        $optionResources['sub_attributes'][$attribute['code']] = [
                            'resource' => $attribute,
                            'type_matcher' => $this->referenceEntityAttributeTypeMatcher->match($attribute['type']),
                        ];
                    } catch (UnsupportedReferenceEntityAttributeTypeException $exception) {
                        $this->logger->notice($exception->getMessage());
                    }
                }

                $payload->setResource($attributeCode, $optionResources);
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
