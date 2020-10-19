<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Task\Product;

use Psr\Log\LoggerInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Generator\SlugGeneratorInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\LocaleAttributeTranslationTask;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AddAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var string[] */
    private $productProperties;

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    private $caseConverter;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var \Sylius\Component\Locale\Context\LocaleContextInterface */
    private $localeContext;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ProductAttributeValueValueBuilder */
    private $attributeValueValueBuilder;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productAttributeRepository;

    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $productTranslationRepository;

    /** @var \Sylius\Component\Resource\Factory\FactoryInterface */
    private $productTranslationFactory;

    /** @var EntityRepository */
    private $productConfigurationRepository;

    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository */
    private $productFiltersRulesRepository;

    /** @var LoggerInterface */
    private $akeneoLogger;

    /** @var ProductFactoryInterface */
    private $productFactory;

    /** @var AkeneoTaskProvider */
    private $taskProvider;

    public function __construct(
        LoggerInterface $akeneoLogger,
        RepositoryInterface $productAttributeRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductAttributeValueValueBuilder $attributeValueValueBuilder,
        EntityRepository $productConfigurationRepository,
        ProductFactoryInterface $productFactory,
        AkeneoTaskProvider $taskProvider
    ) {
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->localeContext = $localeContext;
        $this->attributeValueValueBuilder = $attributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->akeneoLogger = $akeneoLogger;
        $this->productFactory = $productFactory;
        $this->taskProvider = $taskProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload $payload
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || $payload->getProduct() === null) {
            return $payload;
        }

        $this->caseConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach ($payload->getResource()['values'] as $attributeCode => $translations) {
            $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);

            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);
            if (!$attribute instanceof AttributeInterface || null === $attribute->getType()) {
                continue;
            }

            if (!$this->attributeValueValueBuilder->hasSupportedBuilder($attributeCode)) {
                continue;
            }

            $this->setAttributeTranslations($payload, $translations, $attribute, $attributeCode, $payload->getScope());
        }

        return $payload;
    }

    private function setAttributeTranslations(
        ProductResourcePayload $payload,
        array $translations,
        AttributeInterface $attribute,
        string $attributeCode,
        string $scope
    ): void {
        foreach ($translations as $translation) {
            if (!$payload->getProduct() instanceof ProductInterface) {
                return;
            }

            $localeAttributeTranslationPayload = new LocaleAttributeTranslationPayload($payload->getAkeneoPimClient());
            $localeAttributeTranslationPayload
                ->setProduct($payload->getProduct())
                ->setAttribute($attribute)
                ->setTranslations($translations)
                ->setTranslation($translation)
                ->setAttributeCode($attributeCode)
                ->setScope($scope);
            $localeAttributeTranslationTask = $this->taskProvider->get(LocaleAttributeTranslationTask::class);
            $localeAttributeTranslationTask->__invoke($localeAttributeTranslationPayload);
        }
    }
}
