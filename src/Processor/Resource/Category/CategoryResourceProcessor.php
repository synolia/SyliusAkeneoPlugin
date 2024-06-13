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
use Synolia\SyliusAkeneoPlugin\Creator\AttributeCreatorInterface;
use Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\Doctrine\DoctrineSortableManager;
use Synolia\SyliusAkeneoPlugin\Processor\Category\CategoryProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeChoiceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductAttribute\ProductAttributeTableProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\ProductOption\ProductOptionProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\AkeneoResourceProcessorInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Resource\Exception\MaxResourceProcessorRetryException;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyVariantRetrieverInterface;

class CategoryResourceProcessor implements AkeneoResourceProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProductAttributeChoiceProcessorInterface $attributeChoiceProcessor,
        private ProductOptionProcessorInterface $productOptionProcessor,
        private ProductAttributeTableProcessorInterface $productAttributeTableProcessor,
        private EventDispatcherInterface $dispatcher,
        private AttributeCreatorInterface $attributeCreator,
        private FamilyRetrieverInterface $familyRetriever,
        private FamilyVariantRetrieverInterface $familyVariantRetriever,
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
            ++$this->createCount;
            $this->logger->info(Messages::hasBeenCreated($this->type, (string) $taxon->getCode()));

            return $taxon;
        }

        ++$this->updateCount;
        $this->logger->info(Messages::hasBeenUpdated($this->type, (string) $taxon->getCode()));

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
