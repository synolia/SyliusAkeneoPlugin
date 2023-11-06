<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Manager\Doctrine\DoctrineSortableManager;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Processor\Category\CategoryProcessorChainInterface;
use Synolia\SyliusAkeneoPlugin\Provider\Configuration\Api\CategoryConfigurationProviderInterface;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Throwable;

/**
 * @internal
 */
final class CreateUpdateEntityTask implements AkeneoTaskInterface
{
    private int $updateCount = 0;

    private int $createCount = 0;

    private string $type;

    public function __construct(
        private TaxonFactoryInterface $taxonFactory,
        private EntityManagerInterface $entityManager,
        private TaxonRepository $taxonRepository,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        private DoctrineSortableManager $sortableManager,
        private CategoryProcessorChainInterface $processorChain,
        private CategoryConfigurationProviderInterface $categoryConfigurationProvider,
    ) {
    }

    /**
     * @param CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (true === $this->categoryConfigurationProvider->get()->useAkeneoPositions()) {
            $this->sortableManager->disableSortableEventListener();
        }

        foreach ($payload->getResources() as $resource) {
            try {
                $this->dispatcher->dispatch(new BeforeProcessingTaxonEvent($resource));

                if (!$this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->beginTransaction();
                }

                $taxon = $this->getOrCreateEntity($resource['code']);

                $this->processorChain->chain($taxon, $resource);

                $this->dispatcher->dispatch(new AfterProcessingTaxonEvent($resource, $taxon));

                $this->entityManager->flush();

                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->commit();
                }
            } catch (Throwable $throwable) {
                if ($this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->rollback();
                }
                $this->logger->warning($throwable->getMessage());
            }
        }

        $this->sortableManager->enableSortableEventListener();

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
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
}
