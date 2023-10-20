<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AssociationType;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;
use Throwable;

final class BatchAssociationTypesTask extends AbstractBatchTask
{
    private string $type;

    public function __construct(
        EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private FactoryInterface $productAssociationTypeFactory,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
        parent::__construct($entityManager);
    }

    /**
     * @param AssociationTypePayload $payload
     *
     * @throws NoAttributeResourcesException
     * @throws Throwable
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        try {
            $this->entityManager->beginTransaction();

            $query = $this->getSelectStatement($payload);
            /** @var Result $queryResult */
            $queryResult = $query->executeQuery();

            while ($results = $queryResult->fetchAll()) {
                foreach ($results as $result) {
                    /** @var array{code: string, labels: array} $resource */
                    $resource = json_decode($result['values'], true, 512, \JSON_THROW_ON_ERROR);

                    try {
                        if (!$this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->beginTransaction();
                        }

                        $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $resource['code']]);
                        if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
                            /** @var ProductAssociationTypeInterface $productAssociationType */
                            $productAssociationType = $this->productAssociationTypeFactory->createNew();
                            $this->entityManager->persist($productAssociationType);

                            $productAssociationType->setCode($resource['code']);
                        }

                        $this->setTranslations($resource['labels'], $productAssociationType);

                        $this->entityManager->flush();

                        if ($this->entityManager->getConnection()->isTransactionActive()) {
                            $this->entityManager->commit();
                        }

                        $this->entityManager->clear();
                        unset($resource, $productAssociationType);

                        $this->removeEntry($payload, (int) $result['id']);
                    } catch (UnsupportedAttributeTypeException | InvalidAttributeException | ExcludedAttributeException | NotFoundHttpException $throwable) {
                        $this->removeEntry($payload, (int) $result['id']);
                    } catch (Throwable $throwable) {
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
        } catch (Throwable $throwable) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

        return $payload;
    }

    private function setTranslations(array $labels, ProductAssociationTypeInterface $productAssociationType): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($usedLocalesOnBothPlatform);

            $productAssociationType->setCurrentLocale($usedLocalesOnBothPlatform);
            $productAssociationType->setFallbackLocale($usedLocalesOnBothPlatform);

            if (!isset($labels[$akeneoLocale])) {
                $productAssociationType->setName(sprintf('[%s]', $productAssociationType->getCode()));

                continue;
            }

            $productAssociationType->setName($labels[$akeneoLocale]);
        }
    }
}
