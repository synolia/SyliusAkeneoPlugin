<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table("akeneo_api_configuration_attribute_mapping")
 */
final class AttributeMapping
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $sylius;

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $akeneo;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $translate;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSylius(): ?array
    {
        return $this->sylius;
    }

    public function setSylius(array $sylius): self
    {
        $this->sylius = $sylius;

        return $this;
    }

    public function getAkeneo(): ?array
    {
        return $this->akeneo;
    }

    public function setAkeneo(array $akeneo): self
    {
        $this->akeneo = $akeneo;

        return $this;
    }

    public function isTranslate(): ?bool
    {
        return $this->translate;
    }

    public function setTranslate(bool $translate): self
    {
        $this->translate = $translate;

        return $this;
    }
}
