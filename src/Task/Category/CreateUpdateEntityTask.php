<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\Taxon;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoCategoryResourcesException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Model\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;

final class CreateUpdateEntityTask implements AkeneoTaskInterface
{
    /** @var \Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface */
    private $taxonFactory;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $entityManager;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository */
    private $taxonRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var int */
    private $updateCount = 0;

    /** @var int */
    private $createCount = 0;

    /** @var string */
    private $type;

    public function __construct(
        TaxonFactoryInterface $taxonFactory,
        EntityManagerInterface $entityManager,
        TaxonRepository $taxonAkeneoRepository,
        LoggerInterface $logger
    ) {
        $this->taxonFactory = $taxonFactory;
        $this->entityManager = $entityManager;
        $this->taxonRepository = $taxonAkeneoRepository;
        $this->logger = $logger;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        $this->logger->debug(self::class);
        $this->type = $payload->getType();
        $this->logger->notice(Messages::createOrUpdate($this->type));

        if (!is_array($payload->getResources())) {
            throw new NoCategoryResourcesException('No resource found.');
        }

        try {
            $this->entityManager->beginTransaction();

            foreach ($payload->getResources() as $resource) {
                /** @var \Sylius\Component\Core\Model\TaxonInterface $taxon */
                $taxon = $this->getOrCreateEntity($resource['code']);

                $taxons[$resource['code']] = $taxon;

                if (null !== $resource['parent']) {
                    $parent = $taxons[$resource['parent']] ?? null;

                    if (!$parent instanceof Taxon) {
                        continue;
                    }

                    $taxon->setParent($parent);
                }

                foreach ($resource['labels'] as $locale => $label) {
                    $taxon->setCurrentLocale($locale);
                    $taxon->setFallbackLocale($locale);
                    $taxon->setName($label);
                    $taxon->setSlug($resource['code']);
                }
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $throwable) {
            $this->entityManager->rollback();
            $this->logger->warning($throwable->getMessage());

            throw $throwable;
        }

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
