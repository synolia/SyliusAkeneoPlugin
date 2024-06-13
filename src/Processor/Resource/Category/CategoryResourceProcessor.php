<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Resource\Category;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMInvalidArgumentException;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Manager\Doctrine\DoctrineSortableManager;
use Synolia\SyliusAkeneoPlugin\Processor\Category\CategoryProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;

class CategoryResourceProcessor implements AkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventDispatcherInterface $dispatcher,
        private LoggerInterface $akeneoLogger,
        private ManagerRegistry $managerRegistry,
        private TaxonFactoryInterface $taxonFactory,
        private TaxonRepository $taxonRepository,
        private DoctrineSortableManager $sortableManager,
        private CategoryProcessorChainInterface $processorChain,
        private CategoryConfigurationProviderInterface $categoryConfigurationProvider,
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

        if (true === $this->categoryConfigurationProvider->get()->useAkeneoPositions()) {
            $this->sortableManager->disableSortableEventListener();
        }

        try {
            $this->akeneoLogger->notice('Processing category', [
                'code' => $resource['code'] ?? 'unknown',
            ]);

            $this->dispatcher->dispatch(new BeforeProcessingTaxonEvent($resource));

            $taxon = $this->getOrCreateEntity($resource['code']);

            $this->processorChain->chain($taxon, $resource);

            $this->dispatcher->dispatch(new AfterProcessingTaxonEvent($resource, $taxon));

            $this->entityManager->flush();

            if (true === $this->categoryConfigurationProvider->get()->useAkeneoPositions()) {
                $this->sortableManager->enableSortableEventListener();
            }
        } catch (ExcludedAttributeException) {
            // Do nothing
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

    private function getOrCreateEntity(string $code): TaxonInterface
    {
        /** @var TaxonInterface $taxon */
        $taxon = $this->taxonRepository->findOneBy(['code' => $code]);

        if (!$taxon instanceof TaxonInterface) {
            /** @var TaxonInterface $taxon */
            $taxon = $this->taxonFactory->createNew();
            $taxon->setCode($code);
            $this->entityManager->persist($taxon);

            return $taxon;
        }

        return $taxon;
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
