<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Repository\LocaleRepositoryInterface;

class NameProcessor implements NameProcessorInterface
{
    private LocaleRepositoryInterface $localeRepository;

    private RepositoryInterface $productVariantTranslationRepository;

    private FactoryInterface $productVariantTranslationFactory;

    private EntityManagerInterface $entityManager;

    public static function getDefaultPriority(): int
    {
        return 800;
    }

    public function __construct(
        LocaleRepositoryInterface $localeRepository,
        RepositoryInterface $productVariantTranslationRepository,
        FactoryInterface $productVariantTranslationFactory,
        EntityManagerInterface $entityManager
    ) {
        $this->localeRepository = $localeRepository;
        $this->productVariantTranslationRepository = $productVariantTranslationRepository;
        $this->productVariantTranslationFactory = $productVariantTranslationFactory;
        $this->entityManager = $entityManager;
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        foreach ($this->localeRepository->getLocaleCodes() as $locale) {
            /** @var string $name */
            $name = $productVariant->getCode();

            /** @var ProductOptionValueInterface $optionValue */
            foreach ($productVariant->getOptionValues() as $key => $optionValue) {
                if (0 === $key) {
                    $name .= ' ';
                }
                $name .= $optionValue->getTranslation($locale)->getValue() . ' - ';
            }

            if (\substr($name, strlen($name) - 3)) {
                $name = \substr($name, 0, strlen($name) - 3);
            }

            // This does not work if two options are set for the same variant
            $productVariantTranslation = $this->productVariantTranslationRepository->findOneBy([
                'translatable' => $productVariant,
                'locale' => $locale,
            ]);

            if (!$productVariantTranslation instanceof ProductVariantTranslationInterface) {
                /** @var ProductVariantTranslationInterface $productVariantTranslation */
                $productVariantTranslation = $this->productVariantTranslationFactory->createNew();
                $this->entityManager->persist($productVariantTranslation);
                $productVariantTranslation->setLocale($locale);
                $productVariantTranslation->setTranslatable($productVariant);
                $productVariant->addTranslation($productVariantTranslation);
            }

            $productVariantTranslation->setName($name);
        }
    }

    public function support(ProductVariantInterface $productVariant, array $resource): bool
    {
        return $productVariant->getOptionValues()->count() > 0;
    }
}
