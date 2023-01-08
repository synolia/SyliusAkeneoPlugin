<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Category;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Factory\TaxonFactoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
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
        private RepositoryInterface $taxonTranslationRepository,
        private FactoryInterface $taxonTranslationFactory,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
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

        foreach ($payload->getResources() as $resource) {
            try {
                $this->dispatcher->dispatch(new BeforeProcessingTaxonEvent($resource));

                if (!$this->entityManager->getConnection()->isTransactionActive()) {
                    $this->entityManager->beginTransaction();
                }

                $taxon = $this->getOrCreateEntity($resource['code']);

                $taxons[$resource['code']] = $taxon;

                $this->assignParent($taxon, $taxons, $resource);

                foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $locale) {
                    $label = \sprintf('[%s]', $resource['code']);

                    if (array_key_exists($locale, $resource['labels']) && null !== $resource['labels'][$locale]) {
                        $label = $resource['labels'][$locale];
                    }

                    $taxonTranslation = $this->taxonTranslationRepository->findOneBy([
                        'translatable' => $taxon,
                        'locale' => $locale,
                    ]);

                    if (!$taxonTranslation instanceof TaxonTranslationInterface) {
                        /** @var TaxonTranslationInterface $taxonTranslation */
                        $taxonTranslation = $this->taxonTranslationFactory->createNew();
                        $taxonTranslation->setLocale($locale);
                        $taxonTranslation->setTranslatable($taxon);
                        $this->entityManager->persist($taxonTranslation);

                        $this->logger->notice('Created TaxonTranslation', [
                            'taxon_id' => $taxon->getId() ?? 'unknown',
                            'taxon_code' => $taxon->getCode(),
                            'locale' => $locale,
                        ]);
                    }

                    $taxonTranslation->setName($label);
                    $slug = Transliterator::transliterate(
                        str_replace(
                            '\'',
                            '-',
                            sprintf(
                                '%s-%s',
                                $resource['code'],
                                $label,
                            ),
                        ),
                    );
                    $taxonTranslation->setSlug($slug);

                    $this->logger->notice('Update TaxonTranslation', [
                        'taxon_id' => $taxon->getId() ?? 'unknown',
                        'taxon_code' => $taxon->getCode(),
                        'locale' => $locale,
                        'name' => $label,
                        'slug' => $slug,
                    ]);
                }

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

        $this->logger->notice(Messages::countCreateAndUpdate($this->type, $this->createCount, $this->updateCount));

        return $payload;
    }

    private function assignParent(TaxonInterface $taxon, array $taxons, array $resource): void
    {
        if (null === $resource['parent']) {
            return;
        }

        $parent = $taxons[$resource['parent']] ?? null;

        if (!$parent instanceof TaxonInterface) {
            return;
        }
        $taxon->setParent($parent);
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
