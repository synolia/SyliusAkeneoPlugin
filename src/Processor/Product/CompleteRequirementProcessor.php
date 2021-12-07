<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductTranslationInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Entity\ProductConfiguration;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributeDataProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;

final class CompleteRequirementProcessor implements CompleteRequirementProcessorInterface
{
    private AkeneoFamilyPropertiesProviderInterface $akeneoFamilyPropertiesProvider;

    private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider;

    private AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider;

    private ProductFilterRulesProviderInterface $productFilterRulesProvider;

    private EntityRepository $productConfigurationRepository;

    private SlugGeneratorInterface $productSlugGenerator;

    private RepositoryInterface $productTranslationRepository;

    private FactoryInterface $productTranslationFactory;

    public function __construct(
        AkeneoFamilyPropertiesProviderInterface $akeneoFamilyPropertiesProvider,
        SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        AkeneoAttributeDataProviderInterface $akeneoAttributeDataProvider,
        ProductFilterRulesProviderInterface $productFilterRulesProvider,
        EntityRepository $productConfigurationRepository,
        SlugGeneratorInterface $productSlugGenerator,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory
    ) {
        $this->akeneoFamilyPropertiesProvider = $akeneoFamilyPropertiesProvider;
        $this->syliusAkeneoLocaleCodeProvider = $syliusAkeneoLocaleCodeProvider;
        $this->akeneoAttributeDataProvider = $akeneoAttributeDataProvider;
        $this->productFilterRulesProvider = $productFilterRulesProvider;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
    }

    public static function getDefaultPriority(): int
    {
        return 900;
    }

    public function process(ProductInterface $product, array $resource): void
    {
        $missingNameTranslationCount = 0;
        $familyResource = $this->akeneoFamilyPropertiesProvider->getProperties($resource['family']);

        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $productName = null;

            if (isset($resource['values'][$familyResource['attribute_as_label']])) {
                $productName = $this->akeneoAttributeDataProvider->getData(
                    $familyResource['attribute_as_label'],
                    $resource['values'][$familyResource['attribute_as_label']],
                    $usedLocalesOnBothPlatform,
                    $this->productFilterRulesProvider->getProductFiltersRules()->getChannel()
                );
            }

            if (null === $productName) {
                $productName = sprintf('[%s]', $product->getCode());
                ++$missingNameTranslationCount;
            }

            $productTranslation = $this->setProductTranslation($product, $usedLocalesOnBothPlatform, $productName);

            /** @var ProductConfiguration $configuration */
            $configuration = $this->productConfigurationRepository->findOneBy([]);
            if (null !== $product->getId() &&
                null !== $configuration &&
                null !== $productTranslation->getSlug() &&
                false === $configuration->getRegenerateUrlRewrites()) {
                // no regenerate slug if config disable it

                continue;
            }

            if ($missingNameTranslationCount > 0) {
                //Multiple product has the same name
                $productTranslation->setSlug(sprintf(
                    '%s-%s-%d',
                    $resource['code'] ?? $resource['identifier'],
                    $this->productSlugGenerator->generate($productName),
                    $missingNameTranslationCount
                ));

                continue;
            }

            //Multiple product has the same name
            $productTranslation->setSlug(sprintf(
                '%s-%s',
                $resource['code'] ?? $resource['identifier'],
                $this->productSlugGenerator->generate($productName)
            ));
        }
    }

    private function setProductTranslation(ProductInterface $product, string $usedLocalesOnBothPlatform, ?string $productName): ProductTranslationInterface
    {
        $productTranslation = $this->productTranslationRepository->findOneBy([
            'translatable' => $product,
            'locale' => $usedLocalesOnBothPlatform,
        ]);

        if (!$productTranslation instanceof ProductTranslationInterface) {
            /** @var ProductTranslationInterface $productTranslation */
            $productTranslation = $this->productTranslationFactory->createNew();
            $productTranslation->setLocale($usedLocalesOnBothPlatform);
            $product->addTranslation($productTranslation);
        }

        $productTranslation->setName($productName);

        return $productTranslation;
    }
}
