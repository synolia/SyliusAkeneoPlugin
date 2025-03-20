<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Category;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute\TaxonAttributeValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttribute;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeInterface;
use Synolia\SyliusAkeneoPlugin\Entity\TaxonAttributeValueInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute\TaxonAttributeTypeMatcher;
use Webmozart\Assert\Assert;

class AttributeProcessor implements CategoryProcessorInterface
{
    public static function getDefaultPriority(): int
    {
        return 700;
    }

    public function __construct(
        private LoggerInterface $akeneoLogger,
        private EntityManagerInterface $entityManager,
        private RepositoryInterface $taxonAttributeRepository,
        private RepositoryInterface $taxonAttributeValueRepository,
        private FactoryInterface $taxonAttributeFactory,
        private FactoryInterface $taxonAttributeValueFactory,
        private TaxonAttributeTypeMatcher $taxonAttributeTypeMatcher,
        private TaxonAttributeValueBuilder $taxonAttributeValueBuilder,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
    ) {
    }

    public function process(TaxonInterface $taxon, array $resource): void
    {
        foreach ($resource['values'] as $attributeValue) {
            try {
                $this->akeneoLogger->info('Processing category attribute for taxon', [
                    'taxon' => $taxon->getCode(),
                    'attribute' => $attributeValue,
                    'type' => $attributeValue['type'],
                    'locale' => $attributeValue['locale'],
                    'channel' => $attributeValue['channel'],
                ]);

                $taxonAttribute = $this->getTaxonAttributes(
                    $attributeValue['attribute_code'],
                    $attributeValue['type'],
                );

                $value = $this->taxonAttributeValueBuilder->build(
                    $attributeValue['attribute_code'],
                    $attributeValue['type'],
                    $attributeValue['locale'],
                    $attributeValue['channel'],
                    $attributeValue['data'],
                );

                $this->akeneoLogger->info('Set TaxonAttribute value', [
                    'code' => $attributeValue['attribute_code'],
                    'value' => $value,
                ]);

                $this->getTaxonAttributeValues(
                    $attributeValue,
                    $taxon,
                    $taxonAttribute,
                    $value,
                );
            } catch (ExcludedAttributeException | UnsupportedAttributeTypeException $e) {
                $this->akeneoLogger->warning($e->getMessage(), [
                    'trace' => $e->getTrace(),
                    'exception' => $e,
                ]);
            }
        }
    }

    public function support(TaxonInterface $taxon, array $resource): bool
    {
        return $taxon instanceof TaxonAttributeSubjectInterface && array_key_exists('values', $resource);
    }

    private function getTaxonAttributes(string $attributeCode, string $type): TaxonAttributeInterface
    {
        $taxonAttribute = $this->taxonAttributeRepository->findOneBy(['code' => $attributeCode]);

        if ($taxonAttribute instanceof TaxonAttribute) {
            $this->akeneoLogger->debug('Found TaxonAttribute', [
                'code' => $attributeCode,
                'type' => $type,
            ]);

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
        $this->akeneoLogger->debug('Created TaxonAttribute', [
            'code' => $attributeCode,
            'type' => $type,
        ]);

        return $taxonAttribute;
    }

    private function getTaxonAttributeValues(
        array $attributeValue,
        TaxonInterface $taxon,
        TaxonAttributeInterface $taxonAttribute,
        mixed $value,
    ): void {
        Assert::string($taxon->getCode());
        Assert::string($taxonAttribute->getCode());

        if (null === $attributeValue['locale']) {
            $this->handleValue($taxon, $taxonAttribute, null, $value);

            return;
        }

        foreach ($this->syliusAkeneoLocaleCodeProvider->getSyliusLocales($attributeValue['locale']) as $syliusLocale) {
            $this->handleValue($taxon, $taxonAttribute, $syliusLocale, $value);
        }
    }

    private function handleValue(
        TaxonInterface $taxon,
        TaxonAttributeInterface $taxonAttribute,
        ?string $locale,
        mixed $value,
    ): void {
        $taxonAttributeValue = $this->taxonAttributeValueRepository->findOneBy([
            'subject' => $taxon,
            'attribute' => $taxonAttribute,
            'localeCode' => $locale,
        ]);

        if ($taxonAttributeValue instanceof TaxonAttributeValueInterface) {
            $taxonAttributeValue->setValue($value);

            return;
        }

        /** @var TaxonAttributeValueInterface $taxonAttributeValue */
        $taxonAttributeValue = $this->taxonAttributeValueFactory->createNew();
        $taxonAttributeValue->setAttribute($taxonAttribute);
        $taxonAttributeValue->setTaxon($taxon);
        $taxonAttributeValue->setLocaleCode($locale);
        $taxonAttributeValue->setValue($value);

        $this->entityManager->persist($taxonAttributeValue);
    }
}
