<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Factory\AttributeFactory;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\AfterProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\BeforeProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeChoiceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOption\ProductOptionProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ConfigurationProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;
use Webmozart\Assert\Assert;

final class BatchAttributesTask extends AbstractBatchTask
{
    private string $type;

    private RepositoryInterface $productAttributeRepository;

    private FactoryInterface $productAttributeFactory;

    private LoggerInterface $logger;

    private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider;

    private AttributeTypeMatcher $attributeTypeMatcher;

    private AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer;

    private ExcludedAttributesProviderInterface $excludedAttributesProvider;

    private EventDispatcherInterface $dispatcher;

    private ConfigurationProvider $configurationProvider;

    private ProductAttributeChoiceProcessorInterface $attributeChoiceProcessor;

    private ProductOptionProcessorInterface $productOptionProcessor;

    private EditionCheckerInterface $editionChecker;

    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProviderInterface $excludedAttributesProvider,
        ConfigurationProvider $configurationProvider,
        ProductAttributeChoiceProcessorInterface $attributeChoiceProcessor,
        ProductOptionProcessorInterface $productOptionProcessor,
        EventDispatcherInterface $dispatcher,
        EditionCheckerInterface $editionChecker
    ) {
        parent::__construct($entityManager);

        $this->productAttributeRepository = $productAttributeRepository;
        $this->productAttributeFactory = $productAttributeFactory;
        $this->logger = $akeneoLogger;
        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->dispatcher = $dispatcher;
        $this->configurationProvider = $configurationProvider;
        $this->attributeChoiceProcessor = $attributeChoiceProcessor;
        $this->productOptionProcessor = $productOptionProcessor;
        $this->editionChecker = $editionChecker;
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

        try {
            $excludesAttributes = $this->excludedAttributesProvider->getExcludedAttributes();
            $this->entityManager->beginTransaction();

            $query = $this->getSelectStatement($payload);
            $query->executeStatement();

            $variationAxes = array_unique($this->getVariationAxes($payload));
            while ($results = $query->fetchAll()) {
                foreach ($results as $result) {
                    $resource = json_decode($result['values'], true);

                    try {
                        $this->dispatcher->dispatch(new BeforeProcessingAttributeEvent($resource));

                        if (!$this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->beginTransaction();
                        }
                        $attribute = $this->process($excludesAttributes, $resource);

                        //Handle attribute options
                        $this->attributeChoiceProcessor->process($attribute, $resource);

                        //Handler options
                        $this->productOptionProcessor->process($attribute, $variationAxes);

                        $this->dispatcher->dispatch(new AfterProcessingAttributeEvent($resource, $attribute));

                        $this->entityManager->flush();
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->commit();
                        }

                        $this->entityManager->clear();
                        unset($resource, $attribute);

                        $this->removeEntry($payload, (int) $result['id']);
                    } catch (UnsupportedAttributeTypeException | InvalidAttributeException | ExcludedAttributeException | NotFoundHttpException $throwable) {
                        $this->removeEntry($payload, (int) $result['id']);
                    } catch (\Throwable $throwable) {
                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->rollback();
                        }
                        $this->logger->warning($throwable->getMessage());
                    }
                }
            }

            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->commit();
            }
        } catch (\Throwable $throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        return $payload;
    }

    private function process(array $excludesAttributes, array &$resource): AttributeInterface
    {
        //Do not import attributes that must not be used as attribute in Sylius
        if (\in_array($resource['code'], $excludesAttributes, true)) {
            throw new ExcludedAttributeException(sprintf('Attribute "%s" is excluded by configuration.', $resource['code']));
        }

        try {
            $attributeType = $this->attributeTypeMatcher->match($resource['type']);

            $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcher && !$isEnterprise) {
                throw new InvalidAttributeException(sprintf('Attribute "%s" is of type ReferenceEntityAttributeTypeMatcher which is invalid.', $resource['code']));
            }

            $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($resource['code']);

            $attribute = $this->getOrCreateEntity($code, $attributeType);

            $this->setAttributeTranslations($resource['labels'], $attribute);
            $this->entityManager->flush();

            return $attribute;
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->logger->warning(sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage()
            ));

            throw $unsupportedAttributeTypeException;
        }
    }

    private function setAttributeTranslations(array $labels, AttributeInterface $attribute): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $attribute->setCurrentLocale($usedLocalesOnBothPlatform);
            $attribute->setFallbackLocale($usedLocalesOnBothPlatform);

            if (!isset($labels[$usedLocalesOnBothPlatform])) {
                $attribute->setName(sprintf('[%s]', $attribute->getCode()));

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
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $attribute->getCode()));

            return $attribute;
        }

        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $attribute->getCode()));

        return $attribute;
    }

    private function getVariationAxes(PipelinePayloadInterface $payload): array
    {
        Assert::isInstanceOf($payload, AbstractPayload::class);
        $variationAxes = [];
        $client = $payload->getAkeneoPimClient();

        $families = $client->getFamilyApi()->all(
            $this->configurationProvider->getConfiguration()->getPaginationSize()
        );

        foreach ($families as $family) {
            $familyVariants = $client->getFamilyVariantApi()->all(
                $family['code'],
                $this->configurationProvider->getConfiguration()->getPaginationSize()
            );

            $variationAxes = array_merge($variationAxes, $this->getVariationAxesForFamilies($familyVariants));
        }

        return $variationAxes;
    }

    private function getVariationAxesForFamilies(ResourceCursorInterface $familyVariants): array
    {
        $variationAxes = [];

        foreach ($familyVariants as $familyVariant) {
            //Sort array of variant attribute sets by level DESC
            usort($familyVariant['variant_attribute_sets'], function ($leftVariantAttributeSets, $rightVariantAttributeSets) {
                return (int) ($leftVariantAttributeSets['level'] < $rightVariantAttributeSets['level']);
            });

            //We only want to get the last variation set
            foreach ($familyVariant['variant_attribute_sets'][0]['axes'] as $axe) {
                $variationAxes[] = $axe;
            }
        }

        return $variationAxes;
    }
}
