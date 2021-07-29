<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Factory\AttributeFactory;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\AfterProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\BeforeProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;

final class CreateUpdateEntityTask implements AkeneoTaskInterface
{
    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var RepositoryInterface */
    private $productAttributeRepository;

    /** @var FactoryInterface */
    private $productAttributeFactory;

    /** @var LoggerInterface */
    private $logger;

    /** @var SyliusAkeneoLocaleCodeProvider */
    private $syliusAkeneoLocaleCodeProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher */
    private $attributeTypeMatcher;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var ExcludedAttributesProviderInterface */
    private $excludedAttributesProvider;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $apiConfigurationRepository;

    /** @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface */
    private $dispatcher;

    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProviderInterface $excludedAttributesProvider,
        RepositoryInterface $apiConfigurationRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->logger = $akeneoLogger;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->dispatcher = $dispatcher;
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

        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new ApiNotConfiguredException();
        }

        try {
            $excludesAttributes = $this->excludedAttributesProvider->getExcludedAttributes();
            $isEnterprise = $apiConfiguration->isEnterprise() ?? false;
            $this->entityManager->beginTransaction();

            $processedCount = 0;
            $totalItemsCount = $this->count();

            $query = $this->prepareSelectQuery(AttributePayload::SELECT_PAGINATION_SIZE, 0);
            $query->executeStatement();

            while ($results = $query->fetchAll()) {
                foreach ($results as $result) {
                    $resource = \json_decode($result['values'], true);

                    try {
                        $this->dispatcher->dispatch(new BeforeProcessingAttributeEvent($resource));

                        if (!$this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->beginTransaction();
                        }
                        $attribute = $this->process($excludesAttributes, $resource, $isEnterprise);

                        $this->dispatcher->dispatch(new AfterProcessingAttributeEvent($resource, $attribute));

                        $this->entityManager->flush();
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->commit();
                        }
                        $this->entityManager->clear();

                        unset($resource, $attribute);
                    } catch (\Throwable $throwable) {
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->rollback();
                        }
                        $this->logger->warning($throwable->getMessage());
                    }
                }

                $processedCount += \count($results);
                $this->logger->info(\sprintf('Processed %d attributes out of %d.', $processedCount, $totalItemsCount));
                $query = $this->prepareSelectQuery(AttributePayload::SELECT_PAGINATION_SIZE, $processedCount);
                $query->executeStatement();
            }

            $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->commit();
            }
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function process(array $excludesAttributes, array &$resource, bool $isEnterprise): AttributeInterface
    {
        //Do not import attributes that must not be used as attribute in Sylius
        if (\in_array($resource['code'], $excludesAttributes, true)) {
            throw new ExcludedAttributeException(\sprintf(
                'Attribute "%s" is excluded by configuration.',
                $resource['code']
            ));
        }

        try {
            $attributeType = $this->attributeTypeMatcher->match($resource['type']);

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcher && !$isEnterprise) {
                throw new InvalidAttributeException(\sprintf(
                    'Attribute "%s" is of type ReferenceEntityAttributeTypeMatcher which is invalid.',
                    $resource['code']
                ));
            }

            $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($resource['code']);

            $attribute = $this->getOrCreateEntity($code, $attributeType);

            $this->setAttributeTranslations($resource['labels'], $attribute);
            $this->entityManager->flush();

            return $attribute;
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->logger->warning(\sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage()
            ));

            throw $unsupportedAttributeTypeException;
        }
    }

    private function count(): int
    {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT count(id) FROM `%s`',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->executeStatement();

        return (int) \current($query->fetch());
    }

    private function prepareSelectQuery(
        int $limit = AttributePayload::SELECT_PAGINATION_SIZE,
        int $offset = 0
    ): Statement {
        $query = $this->entityManager->getConnection()->prepare(\sprintf(
            'SELECT `values`
             FROM `%s`
             LIMIT :limit
             OFFSET :offset',
            AttributePayload::TEMP_AKENEO_TABLE_NAME
        ));
        $query->bindValue('limit', $limit, ParameterType::INTEGER);
        $query->bindValue('offset', $offset, ParameterType::INTEGER);

        return $query;
    }

    private function setAttributeTranslations(array $labels, AttributeInterface $attribute): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $attribute->setCurrentLocale($usedLocalesOnBothPlatform);
            $attribute->setFallbackLocale($usedLocalesOnBothPlatform);

            if (!isset($labels[$usedLocalesOnBothPlatform])) {
                $attribute->setName(\sprintf('[%s]', $attribute->getCode()));

                continue;
            }

            $attribute->setName($labels[$usedLocalesOnBothPlatform]);
        }
    }

    private function getOrCreateEntity(string $attributeCode, TypeMatcherInterface $attributeType): AttributeInterface
    {
        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

        if (!$attribute instanceof AttributeInterface) {
            if (!$this->productAttributeFactory instanceof AttributeFactory) {
                throw new \LogicException('Wrong Factory');
            }
            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeFactory->createTyped($attributeType->getType());

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcherInterface) {
                $attribute->setStorageType($attributeType->getStorageType());
            }

            $attribute->setCode($attributeCode);
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
