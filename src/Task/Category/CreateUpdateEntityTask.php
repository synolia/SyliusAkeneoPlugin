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
use Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TaxonAttributeValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValueInterface;
use Synolia\SyliusAkeneoPlugin\Event\Category\AfterProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Event\Category\BeforeProcessingTaxonEvent;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Payload\Category\CategoryPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Repository\TaxonRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcher;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class CreateUpdateEntityTask implements AkeneoTaskInterface
{
    private int $updateCount = 0;

    private int $createCount = 0;

    private string $type;

    private array $taxonAttributes = [];

    private array $taxonAttributeValues = [];

    public function __construct(
        private TaxonFactoryInterface $taxonFactory,
        private EntityManagerInterface $entityManager,
        private TaxonRepository $taxonRepository,
        private RepositoryInterface $taxonTranslationRepository,
        private FactoryInterface $taxonTranslationFactory,
        private LoggerInterface $logger,
        private EventDispatcherInterface $dispatcher,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private RepositoryInterface $taxonAttributeRepository,
        private RepositoryInterface $taxonAttributeValueRepository,
        private FactoryInterface $taxonAttributeFactory,
        private FactoryInterface $taxonAttributeValueFactory,
        private TaxonAttributeTypeMatcher $taxonAttributeTypeMatcher,
        private TaxonAttributeValueBuilder $taxonAttributeValueBuilder,
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

                    $this->handleAttributes($taxon, $resource);
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

    private function handleAttributes(TaxonInterface $taxon, array $resource): void
    {
        if (!$taxon instanceof TaxonAttributeSubjectInterface) {
            $this->logger->warning('Missing TaxonAttributeSubjectInterface implementation on Taxon', [
                'taxon_code' => $taxon->getCode(),
            ]);

            return;
        }

        if (!array_key_exists('values', $resource)) {
            return;
        }

        foreach ($resource['values'] as $attributeValue) {
            try {
                $taxonAttribute = $this->getTaxonAttributes(
                    $attributeValue['attribute_code'],
                    $attributeValue['type'],
                );

                $taxonAttributeValue = $this->getTaxonAttributeValues(
                    $taxon,
                    $taxonAttribute,
                    $attributeValue['locale'],
                );

                $value = $this->taxonAttributeValueBuilder->build(
                    $attributeValue['attribute_code'],
                    $attributeValue['type'],
                    $attributeValue['locale'],
                    $attributeValue['channel'],
                    $attributeValue['data'],
                );

                $taxonAttributeValue->setValue($value);
            } catch (UnsupportedAttributeTypeException $e) {
                $this->logger->warning($e->getMessage(), [
                    'trace' => $e->getTrace(),
                    'exception' => $e,
                ]);
            }
        }
    }

    private function getTaxonAttributes(string $attributeCode, string $type): TaxonAttributeInterface
    {
        if (array_key_exists($attributeCode, $this->taxonAttributes)) {
            return $this->taxonAttributes[$attributeCode];
        }

        $taxonAttribute = $this->taxonAttributeRepository->findOneBy(['code' => $attributeCode]);

        if ($taxonAttribute instanceof TaxonAttribute) {
            $this->taxonAttributes[$attributeCode] = $taxonAttribute;

            return $taxonAttribute;
        }

        $matcher = $this->taxonAttributeTypeMatcher->match($type);

        /** @var TaxonAttributeInterface $taxonAttribute */
        $taxonAttribute = $this->taxonAttributeFactory->createNew();
        $taxonAttribute->setCode($attributeCode);
        $taxonAttribute->setType($type);
        $taxonAttribute->setStorageType($matcher->getAttributeType()->getStorageType());
        $taxonAttribute->setTranslatable(false);

        $this->entityManager->persist($taxonAttribute);
        $this->taxonAttributes[$attributeCode] = $taxonAttribute;

        return $taxonAttribute;
    }

    private function getTaxonAttributeValues(
        TaxonInterface $taxon,
        TaxonAttributeInterface $taxonAttribute,
        ?string $locale,
    ): TaxonAttributeValueInterface {
        Assert::string($taxon->getCode());
        Assert::string($taxonAttribute->getCode());

        if (
            array_key_exists($taxon->getCode(), $this->taxonAttributeValues) &&
            array_key_exists($taxonAttribute->getCode(), $this->taxonAttributeValues[$taxon->getCode()])
        ) {
            return $this->taxonAttributeValues[$taxon->getCode()][$taxonAttribute->getCode()];
        }

        $taxonAttributeValue = $this->taxonAttributeValueRepository->findOneBy([
            'subject' => $taxon,
            'attribute' => $taxonAttribute,
            'localeCode' => $locale,
        ]);

        if ($taxonAttributeValue instanceof TaxonAttributeValueInterface) {
            $this->taxonAttributeValues[$taxon->getCode()][$taxonAttribute->getCode()] = $taxonAttributeValue;

            return $taxonAttributeValue;
        }

        /** @var TaxonAttributeValueInterface $taxonAttributeValue */
        $taxonAttributeValue = $this->taxonAttributeValueFactory->createNew();
        $taxonAttributeValue->setAttribute($taxonAttribute);
        $taxonAttributeValue->setTaxon($taxon);
        $taxonAttributeValue->setLocaleCode($locale);
        $this->entityManager->persist($taxonAttributeValue);

        $this->taxonAttributeValues[$taxon->getCode()][$taxonAttribute->getCode()] = $taxonAttributeValue;

        return $taxonAttributeValue;
    }
}
