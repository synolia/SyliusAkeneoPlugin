<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Behat\Transliterator\Transliterator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Sylius\Component\Taxonomy\Model\TaxonTranslationInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

class TranslationProcessor implements CategoryProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 900;
    }

    public function __construct(
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private RepositoryInterface $taxonTranslationRepository,
        private FactoryInterface $taxonTranslationFactory,
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(TaxonInterface $taxon, array $resource): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
            $akeneoLocale = $this->syliusAkeneoLocaleCodeProvider->getAkeneoLocale($syliusLocale);

            $label = \sprintf('[%s]', $resource['code']);

            if (array_key_exists($akeneoLocale, $resource['labels']) && null !== $resource['labels'][$akeneoLocale]) {
                $label = $resource['labels'][$akeneoLocale];
            }

            $taxonTranslation = $this->taxonTranslationRepository->findOneBy([
                'translatable' => $taxon,
                'locale' => $syliusLocale,
            ]);

            if (!$taxonTranslation instanceof TaxonTranslationInterface) {
                /** @var TaxonTranslationInterface $taxonTranslation */
                $taxonTranslation = $this->taxonTranslationFactory->createNew();
                $taxonTranslation->setLocale($syliusLocale);
                $taxonTranslation->setTranslatable($taxon);
                $this->entityManager->persist($taxonTranslation);

                $this->logger->notice('Created TaxonTranslation', [
                    'taxon_id' => $taxon->getId() ?? 'unknown',
                    'taxon_code' => $taxon->getCode(),
                    'locale' => $syliusLocale,
                    'akeneo_locale' => $akeneoLocale,
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
                'locale' => $syliusLocale,
                'akeneo_locale' => $akeneoLocale,
                'name' => $label,
                'slug' => $slug,
            ]);
        }
    }

    public function support(TaxonInterface $taxon, array $resource): bool
    {
        return true;
    }
}
