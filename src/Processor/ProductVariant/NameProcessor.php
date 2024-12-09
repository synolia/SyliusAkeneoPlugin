<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\ProductVariant;

use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Product\Model\ProductOptionValueInterface;
use Sylius\Component\Product\Model\ProductOptionValueTranslationInterface;
use Sylius\Component\Product\Model\ProductVariantTranslationInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

class NameProcessor implements NameProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 800;
    }

    public function __construct(
        private RepositoryInterface $productVariantTranslationRepository,
        private FactoryInterface $productVariantTranslationFactory,
        private EntityManagerInterface $entityManager,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    public function process(ProductVariantInterface $productVariant, array $resource): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $syliusLocale) {
            /** @var string $name */
            $name = $productVariant->getCode();

            /** @var ProductOptionValueInterface $optionValue */
            foreach ($productVariant->getOptionValues() as $key => $optionValue) {
                if (0 === $key) {
                    $name .= ' ';
                }
                /** @var ProductOptionValueTranslationInterface $translation */
                $translation = $optionValue->getTranslation($syliusLocale);
                $name .= $translation->getValue() . ' - ';
            }

            if (\substr($name, strlen($name) - 3)) {
                $name = \substr($name, 0, strlen($name) - 3);
            }

            // This does not work if two options are set for the same variant
            $productVariantTranslation = $this->productVariantTranslationRepository->findOneBy([
                'translatable' => $productVariant,
                'locale' => $syliusLocale,
            ]);

            if (!$productVariantTranslation instanceof ProductVariantTranslationInterface) {
                /** @var ProductVariantTranslationInterface $productVariantTranslation */
                $productVariantTranslation = $this->productVariantTranslationFactory->createNew();
                $this->entityManager->persist($productVariantTranslation);
                $productVariantTranslation->setLocale($syliusLocale);
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
