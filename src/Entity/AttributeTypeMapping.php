<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_attribute_type_mapping")
 */
class AttributeTypeMapping implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $akeneoAttribute;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $attributeType;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAkeneoAttribute(): ?string
    {
        return $this->akeneoAttribute;
    }

    public function setAkeneoAttribute(string $akeneoAttribute): self
    {
        $this->akeneoAttribute = $akeneoAttribute;

        return $this;
    }

    public function getAttributeType(): ?string
    {
        return $this->attributeType;
    }

    public function setAttributeType(string $attributeType): self
    {
        $this->attributeType = $attributeType;

        return $this;
    }
}
