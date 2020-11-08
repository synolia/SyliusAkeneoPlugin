<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;

final class AkeneoAttributeToSyliusAttributeTransformer
{
    /** @var EntityRepository */
    private $attributeAkeneoSyliusMappingRepository;

    /** @var array */
    private $attributeAkeneoSyliusMappings;

    public function __construct(EntityRepository $attributeAkeneoSyliusMappingRepository)
    {
        $this->attributeAkeneoSyliusMappingRepository = $attributeAkeneoSyliusMappingRepository;
    }

    public function transform(string $attribute): string
    {
        if ($this->attributeAkeneoSyliusMappings === null) {
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
