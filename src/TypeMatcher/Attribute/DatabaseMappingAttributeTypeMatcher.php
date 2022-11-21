<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Registry\ServiceRegistryInterface;
use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\Attribute\DatabaseProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;

final class DatabaseMappingAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    private RepositoryInterface $attributeTypeMappingRepository;

    private AttributeTypeMapping $storedAttributeTypeMapping;

    private ServiceRegistryInterface $attributeTypeRegistry;

    public function __construct(
        RepositoryInterface $attributeTypeMappingRepository,
        ServiceRegistryInterface $attributeTypeRegistry
    ) {
        $this->attributeTypeMappingRepository = $attributeTypeMappingRepository;
        $this->attributeTypeRegistry = $attributeTypeRegistry;
    }

    public function getType(): string
    {
        if (!$this->storedAttributeTypeMapping instanceof AttributeTypeMapping) {
            throw new \LogicException('Method support() must be called fist or the type is not supported.');
        }

        if (null === $this->storedAttributeTypeMapping->getAttributeType()) {
            throw new \LogicException('Attribute Type cannot be null.');
        }

        return $this->storedAttributeTypeMapping->getAttributeType();
    }

    public function support(string $akeneoType): bool
    {
        $attributeTypeMapping = $this->attributeTypeMappingRepository->findOneBy([
            'akeneoAttributeType' => $akeneoType,
        ]);

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

    public function getTypeClassName(): string
    {
        if (!$this->storedAttributeTypeMapping instanceof AttributeTypeMapping) {
            throw new \LogicException('Method support() must be called fist or the type is not supported.');
        }

        if (null === $this->storedAttributeTypeMapping->getAttributeType()) {
            throw new \LogicException('Attribute Type cannot be null.');
        }

        return \get_class($this->attributeTypeRegistry->get($this->storedAttributeTypeMapping->getAttributeType()));
    }
}
