<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Factory\AttributeFactory;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\CollectionReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;

final class RetrieveReferenceEntityOptionsTask implements AkeneoTaskInterface
{
    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAttributeFactory;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

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

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider */
    private $excludedAttributesProvider;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    public function __construct(
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProvider $excludedAttributesProvider
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->logger = $akeneoLogger;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
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
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        $optionsPayload = new ReferenceEntityOptionsPayload($payload->getAkeneoPimClient());

        try {
            $options = [];
            $excludesAttributes = $this->excludedAttributesProvider->getExcludedAttributes();

            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                //Do not import attributes that must not be used as attribute in Sylius
                if (\in_array($resource['code'], $excludesAttributes, true)) {
                    continue;
                }

                try {
                    $attributeType = $this->attributeTypeMatcher->match($resource['type']);

                    if (!$attributeType instanceof ReferenceEntityAttributeTypeMatcher &&
                        !$attributeType instanceof CollectionReferenceEntityAttributeTypeMatcher) {
                        continue;
                    }

                    //TODO: find attribute using his code
                    /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
                    $attribute = $this->getOrCreateEntity($resource, $attributeType);

                    $options[$resource['code']] = [
                        'isMultiple' => $attributeType instanceof CollectionReferenceEntityAttributeTypeMatcher,
                        'resources' => $payload->getAkeneoPimClient()->getReferenceEntityRecordApi()->all(
                            $resource['reference_data_name']
                        ),
                        'reference_data_name' => $resource['reference_data_name'],
                        'attribute' => $attribute,
                    ];
                } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
                    $this->logger->warning(\sprintf(
                        '%s: %s',
                        $resource['code'],
                        $unsupportedAttributeTypeException->getMessage()
                    ));

                    continue;
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
        $optionsPayload->setResources($options);

        return $optionsPayload;
    }

    private function getOrCreateEntity(array $resource, AttributeTypeMatcherInterface $attributeType): AttributeInterface
    {
        $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($resource['code']);
        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $code]);

        if (!$attribute instanceof AttributeInterface) {
            if (!$this->productAttributeFactory instanceof AttributeFactory) {
                throw new \LogicException('Wrong Factory');
            }
            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeFactory->createTyped($attributeType->getType());
            $attribute->setCode($code);
            $this->entityManager->persist($attribute);
            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $attribute->getCode()));

            return $attribute;
        }

        ++$this->updateCount;
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $attribute->getCode()));

        return $attribute;
    }
}
