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
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\ProductReferenceEntityAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityAttributeType;
use Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules;
use Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedReferenceEntityAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\LocaleAttributeTranslationPayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoReferenceEntityAttributePropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Repository\ProductFiltersRulesRepository;
use Synolia\SyliusAkeneoPlugin\Task\AkeneoTaskInterface;
use Synolia\SyliusAkeneoPlugin\Task\ReferenceEntity\LocaleAttributeTranslationTask;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AddReferenceEntityAttributesToProductTask implements AkeneoTaskInterface
{
    /** @var string[] */
    private $productProperties;

    /** @var \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter */
    private $caseConverter;

    /** @var \Sylius\Component\Product\Generator\SlugGeneratorInterface */
    private $productSlugGenerator;

    /** @var \Sylius\Component\Locale\Context\LocaleContextInterface */
    private $localeContext;

    /** @var \Synolia\SyliusAkeneoPlugin\Builder\ReferenceEntityAttribute\ProductReferenceEntityAttributeValueValueBuilder */
    private $productReferenceEntityAttributeValueValueBuilder;

    /** @var ProductAttributeRepository */
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

    /** @var AkeneoAttributePropertiesProvider */
    private $akeneoAttributePropertiesProvider;

    /** @var ProductAttributeValueRepositoryInterface */
    private $productAttributeValueRepository;

    /** @var AkeneoReferenceEntityAttributePropertiesProvider */
    private $akeneoReferenceEntityAttributePropertiesProvider;

    public function __construct(
        LoggerInterface $akeneoLogger,
        ProductAttributeRepository $productAttributeAkeneoRepository,
        AkeneoAttributeToSyliusAttributeTransformer $akeneoAttributeToSyliusAttributeTransformer,
        RepositoryInterface $productTranslationRepository,
        FactoryInterface $productTranslationFactory,
        SlugGeneratorInterface $productSlugGenerator,
        LocaleContextInterface $localeContext,
        ProductReferenceEntityAttributeValueValueBuilder $productReferenceEntityAttributeValueValueBuilder,
        EntityRepository $productConfigurationRepository,
        ProductFiltersRulesRepository $productFiltersRulesRepository,
        ProductFactoryInterface $productFactory,
        AkeneoTaskProvider $taskProvider,
        AkeneoAttributePropertiesProvider $akeneoAttributePropertiesProvider,
        ProductAttributeValueRepositoryInterface $productAttributeValueRepository,
        AkeneoReferenceEntityAttributePropertiesProvider $akeneoReferenceEntityAttributePropertiesProvider
    ) {
        $this->productTranslationRepository = $productTranslationRepository;
        $this->productTranslationFactory = $productTranslationFactory;
        $this->productSlugGenerator = $productSlugGenerator;
        $this->localeContext = $localeContext;
        $this->productReferenceEntityAttributeValueValueBuilder = $productReferenceEntityAttributeValueValueBuilder;
        $this->productAttributeRepository = $productAttributeAkeneoRepository;
        $this->productConfigurationRepository = $productConfigurationRepository;
        $this->akeneoAttributeToSyliusAttributeTransformer = $akeneoAttributeToSyliusAttributeTransformer;
        $this->productFiltersRulesRepository = $productFiltersRulesRepository;
        $this->akeneoLogger = $akeneoLogger;
        $this->productFactory = $productFactory;
        $this->taskProvider = $taskProvider;
        $this->akeneoAttributePropertiesProvider = $akeneoAttributePropertiesProvider;
        $this->productAttributeValueRepository = $productAttributeValueRepository;
        $this->akeneoReferenceEntityAttributePropertiesProvider = $akeneoReferenceEntityAttributePropertiesProvider;
    }

    /**
     * @param \Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface $payload
     *
     * @return \Synolia\SyliusAkeneoPlugin\Payload\PipelinePayloadInterface
     * @throws \Synolia\SyliusAkeneoPlugin\Exceptions\NoProductFiltersConfigurationException
     */
    public function __invoke(PipelinePayloadInterface $payload): PipelinePayloadInterface
    {
        if (!$payload instanceof ProductResourcePayload || $payload->getProduct() === null) {
            return $payload;
        }

        /** @var \Synolia\SyliusAkeneoPlugin\Entity\ProductFiltersRules $filters */
        $filters = $this->productFiltersRulesRepository->findOneBy([]);
        if (!$filters instanceof ProductFiltersRules) {
            throw new NoProductFiltersConfigurationException('Product filters must be configured before importing product attributes.');
        }
        $this->akeneoReferenceEntityAttributePropertiesProvider->setLoadsAllAttributesAtOnce(true);
        $scope = $filters->getChannel();

        $this->productProperties = ['name', 'slug', 'description', 'metaDescription'];
        $this->caseConverter = new CamelCaseToSnakeCaseNameConverter();

        foreach ($payload->getResource()['values'] as $attributeCode => $translations) {
            $transformedAttributeCode = $this->akeneoAttributeToSyliusAttributeTransformer->transform($attributeCode);
            if (\in_array($this->caseConverter->denormalize($transformedAttributeCode), $this->productProperties, true)) {
                continue;
            }
            /** @var \Sylius\Component\Attribute\Model\AttributeInterface $attribute */
            $attribute = $this->productAttributeRepository->findOneBy(['code' => $transformedAttributeCode]);
            if (!$attribute instanceof AttributeInterface ||
                null === $attribute->getType() ||
                ReferenceEntityAttributeType::TYPE !== $attribute->getType()
            ) {
                continue;
            }

            $referenceEntityAttributeProperties = $this->akeneoAttributePropertiesProvider->getProperties($attributeCode);

            //get reference entity data code
            $data = $translations[0]['data'];

            //retrieve all records of this reference entity for data $data
            $records = $payload->getAkeneoPimClient()->getReferenceEntityRecordApi()->get($referenceEntityAttributeProperties['reference_data_name'], $data);

            foreach ($records['values'] as $subAttributeCode => $record) {
                try {
                    $akeneoAttributeCode = \str_replace($attributeCode . '_', '', $subAttributeCode);
                    if (!$this->productReferenceEntityAttributeValueValueBuilder->hasSupportedBuilder(
                        $referenceEntityAttributeProperties['reference_data_name'],
                        $akeneoAttributeCode
                    )) {
                        continue;
                    }

                    $subAttribute = $this->productAttributeRepository->findOneBy(['code' => \sprintf(
                        '%s_%s',
                        $attributeCode,
                        $subAttributeCode
                    )]);

                    if (!$subAttribute instanceof AttributeInterface) {
                        //Skipping as the attribute does not exist.
                        continue;
                    }

                    $this->setAttributeTranslations(
                        $payload,
                        $record,
                        $subAttribute,
                        $referenceEntityAttributeProperties['reference_data_name'],
                        $akeneoAttributeCode,
                        $scope
                    );
                } catch (UnsupportedReferenceEntityAttributeTypeException $exception) {
                    $this->akeneoLogger->warning(\sprintf(
                        '%s for sub-attribute %s of reference entity %s for entity %s with data %s',
                        $exception->getMessage(),
                        $subAttributeCode,
                        $attributeCode,
                        $referenceEntityAttributeProperties['reference_data_name'],
                        $data,
                    ));
                } catch (\Throwable $exception) {
                    $this->akeneoLogger->error($exception->getMessage());
                }
            }
        }

        return $payload;
    }

    private function setAttributeTranslations(
        ProductResourcePayload $payload,
        array $translations,
        AttributeInterface $attribute,
        string $referenceEntityCode,
        string $subAttributeCode,
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
                ->setReferenceEntityCode($referenceEntityCode)
                ->setAttributeCode($subAttributeCode)
                ->setScope($scope);
            $localeAttributeTranslationTask = $this->taskProvider->get(LocaleAttributeTranslationTask::class);
            $localeAttributeTranslationTask->__invoke($localeAttributeTranslationPayload);
        }
    }
}
