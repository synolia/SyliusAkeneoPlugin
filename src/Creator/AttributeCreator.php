<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Creator;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sylius\Component\Attribute\AttributeType\AttributeTypeInterface;
use Sylius\Component\Attribute\Factory\AttributeFactoryInterface;
use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Product\Model\ProductAttributeValue;
use Sylius\Component\Product\Repository\ProductAttributeValueRepositoryInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Checker\EditionCheckerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\ExcludedAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Attribute\InvalidAttributeException;
use Synolia\SyliusAkeneoPlugin\Exceptions\Transformer\DataMigration\NoDataMigrationTransformerFoundException;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\Logger\Messages;
use Synolia\SyliusAkeneoPlugin\Provider\ExcludedAttributesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Provider\SyliusAkeneoLocaleCodeProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Transformer\DataMigration\DataMigrationTransformer;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\ReferenceEntityAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute\ReferenceEntityAttributeTypeMatcherInterface;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\TypeMatcherInterface;
use Webmozart\Assert\Assert;

final class AttributeCreator implements AttributeCreatorInterface
{
    public function __construct(
        private FactoryInterface $productAttributeFactory,
        private SyliusAkeneoLocaleCodeProvider $syliusAkeneoLocaleCodeProvider,
        private EntityManagerInterface $entityManager,
        private RepositoryInterface $productAttributeRepository,
        private AkeneoAttributeToSyliusAttributeTransformerInterface $akeneoAttributeToSyliusAttributeTransformer,
        private AttributeTypeMatcher $attributeTypeMatcher,
        private LoggerInterface $logger,
        private ExcludedAttributesProviderInterface $excludedAttributesProvider,
        private EditionCheckerInterface $editionChecker,
        private ProductAttributeValueRepositoryInterface $productAttributeValueRepository,
        private DataMigrationTransformer $dataMigrationTransformer,
    ) {
    }

    /**
     * @throws InvalidAttributeException
     * @throws UnsupportedAttributeTypeException
     * @throws ExcludedAttributeException
     */
    public function create(array $resource): AttributeInterface
    {
        $excludesAttributes = $this->excludedAttributesProvider->getExcludedAttributes();

        //Do not import attributes that must not be used as attribute in Sylius
        if (\in_array($resource['code'], $excludesAttributes, true)) {
            throw new ExcludedAttributeException(sprintf('Attribute "%s" is excluded by configuration.', $resource['code']));
        }

        try {
            $attributeType = $this->attributeTypeMatcher->match($resource['type']);

            $isEnterprise = $this->editionChecker->isEnterprise() || $this->editionChecker->isSerenityEdition();

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcher && !$isEnterprise) {
                throw new InvalidAttributeException(sprintf('Attribute "%s" is of type ReferenceEntityAttributeTypeMatcher which is invalid.', $resource['code']));
            }

            $code = $this->akeneoAttributeToSyliusAttributeTransformer->transform($resource['code']);

            $attribute = $this->getOrCreateEntity($code, $attributeType);

            $this->setAttributeTranslations($resource['labels'], $attribute);

            return $attribute;
        } catch (UnsupportedAttributeTypeException $unsupportedAttributeTypeException) {
            $this->logger->warning(sprintf(
                '%s: %s',
                $resource['code'],
                $unsupportedAttributeTypeException->getMessage(),
            ));

            throw $unsupportedAttributeTypeException;
        }
    }

    private function setAttributeTranslations(array $labels, AttributeInterface $attribute): void
    {
        foreach ($this->syliusAkeneoLocaleCodeProvider->getUsedLocalesOnBothPlatforms() as $usedLocalesOnBothPlatform) {
            $attribute->setCurrentLocale($usedLocalesOnBothPlatform);
            $attribute->setFallbackLocale($usedLocalesOnBothPlatform);

            if (!isset($labels[$usedLocalesOnBothPlatform])) {
                $attribute->setName(sprintf('[%s]', $attribute->getCode()));

                continue;
            }

            $attribute->setName($labels[$usedLocalesOnBothPlatform]);
        }
    }

    private function getOrCreateEntity(string $attributeCode, TypeMatcherInterface $attributeType): AttributeInterface
    {
        /** @var AttributeInterface $attribute */
        $attribute = $this->productAttributeRepository->findOneBy(['code' => $attributeCode]);

        if (!$attribute instanceof AttributeInterface) {
            if (!$this->productAttributeFactory instanceof AttributeFactoryInterface) {
                throw new \LogicException('Wrong Factory');
            }
            /** @var AttributeInterface $attribute */
            $attribute = $this->productAttributeFactory->createTyped($attributeType->getType());

            if ($attributeType instanceof ReferenceEntityAttributeTypeMatcherInterface) {
                $attribute->setStorageType($attributeType->getStorageType());
            }

            $attribute->setCode($attributeCode);
            $this->entityManager->persist($attribute);
            $this->logger->info(Messages::hasBeenCreated('Attribute', (string) $attribute->getCode()));

            return $attribute;
        }

        $this->migrateType($attribute, $attributeType);

        $this->logger->info(Messages::hasBeenUpdated('Attribute', (string) $attribute->getCode()));

        return $attribute;
    }

    private function migrateType(AttributeInterface $attribute, TypeMatcherInterface $attributeType): void
    {
        if ($attribute->getType() === $attributeType->getType()) {
            return;
        }

        $attributeTypeClassName = $attributeType->getTypeClassName();
        $attributeTypeObject = new $attributeTypeClassName();

        if (!$attributeTypeObject instanceof AttributeTypeInterface) {
            return;
        }

        $newStorageType = $attributeTypeObject->getStorageType();

        Assert::string($attribute->getType());
        Assert::string($attribute->getStorageType());

        try {
            $this->tryUpgradeData(
                $attribute,
                $attribute->getType(),
                $attributeType->getType(),
                $attribute->getStorageType(),
                $newStorageType,
            );
        } catch (NoDataMigrationTransformerFoundException) {
        }

        $attribute->setType($attributeType->getType());
        $attribute->setStorageType($newStorageType);
    }

    private function tryUpgradeData(
        AttributeInterface $attribute,
        string $fromType,
        string $toType,
        string $fromStorageType,
        string $toStorageType,
    ): void {
        /** @var ProductAttributeValue[] $attributeValues */
        $attributeValues = $this->productAttributeValueRepository->findBy(['attribute' => $attribute]);

        foreach ($attributeValues as $attributeValue) {
            $oldValue = $attributeValue->getValue();
            $newValue = $this->dataMigrationTransformer->transform($fromType, $toType, $oldValue);
            $attributeValue->setValue(null);

            $attribute->setStorageType($toStorageType);
            $attributeValue->setValue($newValue);
            $attribute->setStorageType($fromStorageType);
        }
    }
}
