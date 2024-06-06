<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\AssociationType;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sylius\Component\Product\Model\ProductAssociationTypeInterface;
use Sylius\Component\Product\Repository\ProductAssociationTypeRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

class AssociationTypeResourceProcessor implements AkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $akeneoLogger,
        private FactoryInterface $productAssociationTypeFactory,
        private ProductAssociationTypeRepositoryInterface $productAssociationTypeRepository,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private ManagerRegistry $managerRegistry,
        private int $maxRetryCount,
        private int $retryWaitTime,
        private int $retryCount = 0,
    ) {
    }

    /**
     * @throws MaxResourceProcessorRetryException
     */
    public function process(array $resource): void
    {
        if ($this->retryCount === $this->maxRetryCount) {
            $this->retryCount = 0;

            throw new MaxResourceProcessorRetryException();
        }

        try {
            $this->akeneoLogger->notice('Association Type', [
                'code' => $resource['code'] ?? 'unknown',
            ]);

            $productAssociationType = $this->productAssociationTypeRepository->findOneBy(['code' => $resource['code']]);
            if (!$productAssociationType instanceof ProductAssociationTypeInterface) {
                /** @var ProductAssociationTypeInterface $productAssociationType */
                $productAssociationType = $this->productAssociationTypeFactory->createNew();
                $this->entityManager->persist($productAssociationType);

                $productAssociationType->setCode($resource['code']);
            }

            $this->setTranslations($resource['labels'], $productAssociationType);

            $this->entityManager->flush();
        } catch (ORMInvalidArgumentException $ormInvalidArgumentException) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'product' => $resource,
                'retry_count' => $this->retryCount,
                'error' => $ormInvalidArgumentException->getMessage(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        } catch (\Throwable $throwable) {
            ++$this->retryCount;
            usleep($this->retryWaitTime);

            $this->akeneoLogger->error('Retrying import', [
                'message' => $throwable->getMessage(),
                'trace' => $throwable->getTraceAsString(),
            ]);

            $this->entityManager = $this->getNewEntityManager();
            $this->process($resource);
        }
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

    private function getNewEntityManager(): EntityManagerInterface
    {
        $objectManager = $this->managerRegistry->resetManager();

        if (!$objectManager instanceof EntityManagerInterface) {
            throw new \LogicException('Wrong ObjectManager');
        }

        return $objectManager;
    }
}
