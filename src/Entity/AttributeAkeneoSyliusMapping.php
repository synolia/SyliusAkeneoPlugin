<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

#[ORM\Entity]
#[ORM\Table(name: 'akeneo_attribute_akeneo_sylius_mapping')]
class AttributeAkeneoSyliusMapping implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private $id;

    #[ORM\Column(type: Types::STRING)]
    private ?string $akeneoAttribute = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $syliusAttribute = null;

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
}
