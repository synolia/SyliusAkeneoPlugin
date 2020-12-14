<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;

final class AkeneoAttributeToSyliusAttributeTransformer
{
    private EntityRepository $attributeAkeneoSyliusMappingRepository;

    private ?array $attributeAkeneoSyliusMappings = null;

    public function __construct(EntityRepository $attributeAkeneoSyliusMappingRepository)
    {
        $this->attributeAkeneoSyliusMappingRepository = $attributeAkeneoSyliusMappingRepository;
    }

    public function transform(string $attribute): string
    {
        if (empty($this->attributeAkeneoSyliusMappings)) {
            $this->attributeAkeneoSyliusMappings = $this->attributeAkeneoSyliusMappingRepository->findAll();
        }

        /** @var AttributeAkeneoSyliusMapping $attributeAkeneoSyliusMapping */
        foreach ($this->attributeAkeneoSyliusMappings as $attributeAkeneoSyliusMapping) {
            if ($attributeAkeneoSyliusMapping->getAkeneoAttribute() !== $attribute || $attributeAkeneoSyliusMapping->getSyliusAttribute() === null) {
                continue;
            }
            $attribute = $attributeAkeneoSyliusMapping->getSyliusAttribute();
        }

        return $attribute;
    }
}
