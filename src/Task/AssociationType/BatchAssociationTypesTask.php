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
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Task\AbstractBatchTask;

final class BatchAssociationTypesTask extends AbstractBatchTask
{
    /** @var string */
    private $type;

    /** @var LoggerInterface */
    private $logger;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAssociationTypeFactory;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productAssociationTypeTranslationFactory;

    /** @var \Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface */
    private $productAssociationTypeRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        LoggerInterface $akeneoLogger,
        FactoryInterface $productAssociationTypeFactory,
        FactoryInterface $productAssociationTypeTranslationFactory,
        ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository
    ) {
        parent::__construct($entityManager);

        $this->logger = $akeneoLogger;
        $this->productAssociationTypeFactory = $productAssociationTypeFactory;
        $this->productAssociationTypeTranslationFactory = $productAssociationTypeTranslationFactory;
        $this->productAssociationTypeRepository = $productAssociationTypeRepository;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload $payload
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
            $this->entityManager->beginTransaction();

            $query = $this->getSelectStatement($payload);
            $query->executeStatement();

            while ($results = $query->fetchAll()) {
                foreach ($results as $result) {
                    $resource = \json_decode($result['values'], true);

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

    private function addTranslations(array $resource, ProductAssociationTypeInterface $productAssociationType): void
    {
        foreach ($resource['labels'] as $localeCode => $label) {
            $productAssociationType->addTranslation($this->createTranslation($localeCode, $label));
        }
    }

    private function createTranslation(string $localeCode, string $label): ProductAssociationTypeTranslationInterface
    {
        $productAssociationTypeTranslation = $this->productAssociationTypeTranslationFactory->createNew();
        if (!$productAssociationTypeTranslation instanceof ProductAssociationTypeTranslationInterface) {
            throw new \LogicException('Unknown error.');
        }

        $productAssociationTypeTranslation->setLocale($localeCode);
        $productAssociationTypeTranslation->setName($label);

        return $productAssociationTypeTranslation;
    }
}
