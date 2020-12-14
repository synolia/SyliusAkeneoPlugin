<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Exceptions\ApiNotConfiguredException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProvider;
use Synolia\SyliusAkeneoPlugin\Service\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Throwable;

final class CreateUpdateEntityTask extends AbstractAttributeTask implements AkeneoTaskInterface
{
    private AttributeTypeMatcher $attributeTypeMatcher;

    private AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer;

    private ExcludedAttributesProvider $excludedAttributesProvider;

    private RepositoryInterface $apiConfigurationRepository;

    public function __construct(
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        EntityManagerInterface $entityManager,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        FactoryInterface $productAttributeFactory,
        AttributeTypeMatcher $attributeTypeMatcher,
        LoggerInterface $akeneoLogger,
        ExcludedAttributesProvider $excludedAttributesProvider,
        RepositoryInterface $apiConfigurationRepository
    ) {
        parent::__construct(
            $entityManager,
            $syliusAkeneoLocaleCodeProvider,
            $productAttributeRepository,
            $productAttributeFactory,
            $akeneoLogger
        );

        $this->attributeTypeMatcher = $attributeTypeMatcher;
        $this->excludedAttributesProvider = $excludedAttributesProvider;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->apiConfigurationRepository = $apiConfigurationRepository;
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
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        /** @var ApiConfiguration|null $apiConfiguration */
        $apiConfiguration = $this->apiConfigurationRepository->findOneBy([]);

        if (!$apiConfiguration instanceof ApiConfiguration) {
            throw new ApiNotConfiguredException();
        }

        if (!$payload->getResources() instanceof ResourceCursorInterface) {
            throw new NoAttributeResourcesException('No resource found.');
        }

        try {
            $excludesAttributes = $this->excludedAttributesProvider->getExcludedAttributes();

            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                //Do not import attributes that must not be used as attribute in Sylius
                if (\in_array($resource['code'], $excludesAttributes, true)) {
                    continue;
                }

                try {
                    $attributeType = $this->attributeTypeMatcher->match($resource['type']);

                    if ($attributeType instanceof ReferenceEntityAttributeTypeMatcher &&
                        !$apiConfiguration->isEnterprise()) {
                        continue;
                    }

                    $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($resource['code']);

                    /** @var AttributeInterface $attribute */
                    $attribute = $this->getOrCreateEntity($code, $attributeType);

                    $this->setAttributeTranslations($resource['labels'], $attribute);
                    $this->entityManager->flush();
                } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
                    $this->logger->warning(\sprintf(
                        '%s: %s',
                        $resource['code'],
                        $unsupportedAttributeTypeException->getMessage()
                    ));

                    continue;
                }
            }

            $this->entityManager->commit();
        } catch (Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }
}
