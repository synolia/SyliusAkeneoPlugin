<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\DatabaseProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Component\Cache\CacheKey;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;

final class DatabaseMappingAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private AttributeTypeMapping $storedAttributeTypeMapping;

    public function __construct(
        private RepositoryInterface $attributeTypeMappingRepository,
        private ServiceRegistryInterface $attributeTypeRegistry,
        private CacheInterface $akeneoAttributeTypeMappingCache,
    ) {
    }

    public function getType(): string
    {
        if (null === $this->storedAttributeTypeMapping->getAttributeType()) {
            throw new \LogicException('Attribute Type cannot be null.');
        }

        return $this->storedAttributeTypeMapping->getAttributeType();
    }

    public function support(string $akeneoType): bool
    {
        $attributeTypeMapping = $this->getAttributeTypeMapping($akeneoType);

        if (!$attributeTypeMapping instanceof AttributeTypeMapping) {
            return false;
        }

        $this->storedAttributeTypeMapping = $attributeTypeMapping;

        return true;
    }

    public function getBuilder(): string
    {
        return DatabaseProductAttributeValueValueBuilder::class;
    }

    private function getAttributeTypeMapping(string $akeneoType): ?AttributeTypeMapping
    {
        /** @phpstan-ignore-next-line */
        return $this->akeneoAttributeTypeMappingCache->get(\sprintf(CacheKey::ATTRIBUTE_TYPE_MAPPING, $akeneoType), function () use ($akeneoType): array {
            /** @phpstan-ignore-next-line */
            return $this->attributeTypeMappingRepository->findOneBy([
                'akeneoAttributeType' => $akeneoType,
            ]);
        });
    }

    public function getTypeClassName(): string
    {
        if (null === $this->storedAttributeTypeMapping->getAttributeType()) {
            throw new \LogicException('Attribute Type cannot be null.');
        }

        return $this->attributeTypeRegistry->get($this->storedAttributeTypeMapping->getAttributeType())::class;
    }
}
