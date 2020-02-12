<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass="Synolia\SyliusAkeneoPlugin\Repository\AkeneoCategoriesConfigurationRepository")
 * @ORM\Table("akeneo_api_configuration_categories")
 */
final class AkeneoCategoriesConfiguration implements ResourceInterface
{
    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $activeNewCategories;

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    private $notImportCategories;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $mainCategory;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $rootCategory;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $emptyLocalReplaceBy;

    /**
     * @var ArrayCollection
     * @ORM\ManyToOne(targetEntity="Synolia\SyliusAkeneoPlugin\Entity\AttributeMapping", inversedBy="attributes")
     * @ORM\JoinColumn(name="categories_configuration", referencedColumnName="id")
     */
    private $attributeMapping;

    public function getId(): int
    {
        return $this->id;
    }

    public function isActiveNewCategories(): ?bool
    {
        return $this->activeNewCategories;
    }

    public function setActiveNewCategories(bool $activeNewCategories): self
    {
        $this->activeNewCategories = $activeNewCategories;

        return $this;
    }

    public function getNotImportCategories(): ?array
    {
        return $this->notImportCategories;
    }

    public function setNotImportCategories(array $notImportCategories): self
    {
        $this->notImportCategories = $notImportCategories;

        return $this;
    }

    public function getMainCategory(): ?string
    {
        return $this->mainCategory;
    }

    public function setMainCategory(string $mainCategory): self
    {
        $this->mainCategory = $mainCategory;

        return $this;
    }

    public function getRootCategory(): ?string
    {
        return $this->rootCategory;
    }

    public function setRootCategory(string $rootCategory): self
    {
        $this->rootCategory = $rootCategory;

        return $this;
    }

    public function getEmptyLocalReplaceBy(): ?string
    {
        return $this->emptyLocalReplaceBy;
    }

    public function setEmptyLocalReplaceBy(string $emptyLocalReplaceBy): self
    {
        $this->emptyLocalReplaceBy = $emptyLocalReplaceBy;

        return $this;
    }

    public function getAttributeMapping(): ?ArrayCollection
    {
        return $this->attributeMapping;
    }

    public function addAttributeMapping(AttributeMapping $attributeMapping): self
    {
        $this->attributeMapping->add($attributeMapping);

        return $this;
    }

    public function removeAttributeMapping(AttributeMapping $attributeMapping): void
    {
        $this->attributeMapping->removeElement($attributeMapping);
    }
}
