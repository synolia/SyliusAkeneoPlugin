<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 *
 * @ORM\Table("akeneo_attribute_type_mapping")
 */
#[ORM\Entity]
#[ORM\Table(name: 'akeneo_attribute_type_mapping')]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
class AttributeTypeMapping implements ResourceInterface
{
    /**
     * @var int
     *
     * @ORM\Id()
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    /** @ORM\Column(type="string", unique=true) */
    #[ORM\Column(type: Types::STRING, unique: true)]
    private ?string $akeneoAttributeType = null;

    /** @ORM\Column(type="string") */
    #[ORM\Column(type: Types::STRING)]
    private ?string $attributeType = null;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAkeneoAttributeType(): ?string
    {
        return $this->akeneoAttributeType;
    }

    public function setAkeneoAttributeType(string $akeneoAttributeType): self
    {
        $this->akeneoAttributeType = $akeneoAttributeType;

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
