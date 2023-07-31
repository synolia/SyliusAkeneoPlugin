<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Attribute;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Creator\AttributeCreatorInterface;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\AfterProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Event\Attribute\BeforeProcessingAttributeEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeChoiceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeTableProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOption\ProductOptionProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\ApiConnectionProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;
use Webmozart\Assert\Assert;

final class BatchAttributesTask extends AbstractBatchTask
{
    public function __construct(
        EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private ProductAttributeChoiceProcessorInterface $attributeChoiceProcessor,
        private ProductOptionProcessorInterface $productOptionProcessor,
        private ProductAttributeTableProcessorInterface $productAttributeTableProcessor,
        private EventDispatcherInterface $dispatcher,
        private ApiConnectionProviderInterface $apiConnectionProvider,
        private AttributeCreatorInterface $attributeCreator,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param AttributePayload $payload
     *
     * @throws NoAttributeResourcesException
     * @throws \Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($type));

        try {
            $this->entityManager->beginTransaction();

            $query = $this->getSelectStatement($payload);
            /** @var Result $queryResult */
            $queryResult = $query->executeQuery();

            $variationAxes = array_unique($this->getVariationAxes($payload));
            while ($results = $queryResult->fetchAll()) {
                foreach ($results as $result) {
                    $resource = json_decode($result['values'], true, 512, \JSON_THROW_ON_ERROR);

                    if (!is_array($resource)) {
                        throw new InvalidAttributeException();
                    }

                    try {
                        $this->dispatcher->dispatch(new BeforeProcessingAttributeEvent($resource));

                        if (!$this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->beginTransaction();
                        }

                        $attribute = $this->attributeCreator->create($resource);
                        $this->entityManager->flush();

                        //Handle attribute options
                        $this->attributeChoiceProcessor->process($attribute, $resource);

                        //Handle attribute table configuration
                        $this->productAttributeTableProcessor->process($attribute, $resource);

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

    private function getVariationAxes(PipelinePayloadInterface $payload): array
    {
        Assert::isInstanceOf($payload, AbstractPayload::class);
        $variationAxes = [];
        $client = $payload->getAkeneoPimClient();
        $pagination = $this->apiConnectionProvider->get()->getPaginationSize();

        $families = $client->getFamilyApi()->all($pagination);

        foreach ($families as $family) {
            $familyVariants = $client->getFamilyVariantApi()->all(
                $family['code'],
                $pagination,
            );

            $variationAxes = array_merge($variationAxes, $this->getVariationAxesForFamilies($familyVariants));
        }

        return $variationAxes;
    }

    private function getVariationAxesForFamilies(ResourceCursorInterface $familyVariants): array
    {
        $variationAxes = [];

        /** @var array{variant_attribute_sets: array} $familyVariant */
        foreach ($familyVariants as $familyVariant) {
            /** @var array{axes: array} $variantAttributeSet */
            foreach ($familyVariant['variant_attribute_sets'] as $variantAttributeSet) {
                foreach ($variantAttributeSet['axes'] as $axe) {
                    $variationAxes[] = $axe;
                }
            }
        }

        return $variationAxes;
    }
}
