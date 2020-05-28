<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Sylius\Component\Resource\Repository\RepositoryInterface;
use Synolia\SyliusAkeneoPlugin\Builder\DatabaseProductAttributeValueValueBuilder;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;

final class DatabaseMappingAttributeTypeMatcher implements AttributeTypeMatcherInterface
{
    /** @var \Sylius\Component\Resource\Repository\RepositoryInterface */
    private $attributeTypeMappingRepository;

    /** @var \Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping */
    private $storedAttributeTypeMapping;

    public function __construct(RepositoryInterface $attributeTypeMappingRepository)
    {
        $this->attributeTypeMappingRepository = $attributeTypeMappingRepository;
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
}
