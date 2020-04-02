<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_attribute_akeneo_sylius_mapping")
 */
class AttributeAkeneoSyliusMapping implements ResourceInterface
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
    private $syliusAttribute;

    /**
     * @var string|null
     * @ORM\Column(type="string", nullable=true)
     */
    private $partOfModel;

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

    public function getSyliusAttribute(): ?string
    {
        return $this->syliusAttribute;
    }

    public function setSyliusAttribute(string $syliusAttribute): self
    {
        $this->syliusAttribute = $syliusAttribute;

        return $this;
    }

    public function getPartOfModel(): ?string
    {
        return $this->partOfModel;
    }

    public function setPartOfModel(?string $partOfModel): self
    {
        $this->partOfModel = $partOfModel;

        return $this;
    }
}
