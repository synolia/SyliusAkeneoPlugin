<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Product\Model\ProductTranslationInterface;
use Sylius\Component\Resource\Model\TranslatableTrait;

/**
 * @ApiResource()
 *
 * @ORM\Entity()
 *
 * @ORM\Table(name="akeneo_taxon_attributes")
 */
class TaxonAttribute implements TaxonAttributeInterface, \Stringable
{
    use TranslatableTrait {
        TranslatableTrait::__construct as private initializeTranslationsCollection;
        TranslatableTrait::getTranslation as private doGetTranslation;
    }

    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    protected ?int $id = null;

    /** @ORM\Column(name="code", type="string", length=255, unique=true) */
    protected string $code = '';

    /** @ORM\Column(name="type", type="string", length=255) */
    protected string $type = TextAttributeType::TYPE;

    /** @ORM\Column(name="configuration", type="array") */
    protected array $configuration = [];

    /** @ORM\Column(name="storage_type", type="string", length=255) */
    protected string $storageType = '';

    /** @ORM\Column(name="position", type="integer") */
    protected int $position = 0;

    /** @ORM\Column(name="translatable", type="boolean") */
    protected bool $translatable = true;

    /** @ORM\Column(name="created_at", type="datetime", nullable=true) */
    protected ?\DateTimeInterface $createdAt;

    /** @ORM\Column(name="updated_at", type="datetime", nullable=true) */
    protected ?\DateTimeInterface $updatedAt;

    /** @ORM\OneToMany(
     *     targetEntity="TaxonAttributeValue",
     *     mappedBy="attribute" ,
     *     cascade={"persist", "remove"},
     *     orphanRemoval=true
     * )
     */
    protected Collection $values;

    public function __construct()
    {
        $this->initializeTranslationsCollection();

        $this->createdAt = new \DateTime();
        $this->setCurrentLocale('fr_FR');
    }

    public function getNameByLocaleCode(string $localeCode): string
    {
        /** @var ProductTranslationInterface $translation */
        $translation = $this->getTranslation($localeCode);

        return $translation->getName();
    }

    protected function createTranslation(): AttributeTranslationInterface
    {
        return new TaxonAttributeTranslation();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getName(): ?string
    {
        return $this->getTranslation()->getName();
    }

    public function setName(?string $name): void
    {
        $this->getTranslation()->setName($name);
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getStorageType(): ?string
    {
        return $this->storageType;
    }

    public function setStorageType(?string $storageType): void
    {
        $this->storageType = $storageType;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    public function setTranslatable(bool $translatable): void
    {
        $this->translatable = $translatable;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}