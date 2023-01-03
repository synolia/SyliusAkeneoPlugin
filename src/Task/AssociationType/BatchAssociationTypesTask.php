<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\AssociationType;

use Akeneo\Pim\ApiClient\Exception\NotFoundHttpException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeTranslationInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoAttributeResourcesException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;
use Throwable;

final class BatchAssociationTypesTask extends AbstractBatchTask
{
    private string $type;

    public function __construct(
        EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private FactoryInterface $productAssociationTypeFactory,
        private FactoryInterface $productAssociationTypeTranslationFactory,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        private RepositoryInterface $productAssociationTypeTranslationRepository,
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
            $query->executeStatement();

            while ($results = $query->fetchAll()) {
                foreach ($results as $result) {
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

                        $this->addTranslations($resource, $productAssociationType);

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

    private function addTranslations(array $resource, ProductAssociationTypeInterface $productAssociationType): void
    {
        foreach ($resource['labels'] as $localeCode => $label) {
            $productAssociationTypeTranslation = $this->productAssociationTypeTranslationRepository->findOneBy([
                'translatable' => $productAssociationType,
                'locale' => $localeCode,
            ]);

            if (!$productAssociationTypeTranslation instanceof ProductAssociationTypeTranslationInterface) {
                $productAssociationTypeTranslation = $this->createTranslation($localeCode, $label);
            }

            $productAssociationType->addTranslation($productAssociationTypeTranslation);
        }
    }

    private function createTranslation(string $localeCode, string $label): ProductAssociationTypeTranslationInterface
    {
        /** @var ProductAssociationTypeTranslationInterface $productAssociationTypeTranslation */
        $productAssociationTypeTranslation = $this->productAssociationTypeTranslationFactory->createNew();
        $productAssociationTypeTranslation->setLocale($localeCode);
        $productAssociationTypeTranslation->setName($label);
        $this->entityManager->persist($productAssociationTypeTranslation);

        return $productAssociationTypeTranslation;
    }
}
