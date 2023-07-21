<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Resource\Model\TranslatableInterface;
use Webmozart\Assert\Assert;

/**
 * @ApiResource()
 *
 * @ORM\Entity()
 *
 * @ORM\Table(
 *     name="akeneo_taxon_attribute_translations",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="attribute_translation", columns={"translatable_id", "locale"})}
 * )
 */
#[ApiResource()]
#[ORM\Entity()]
#[ORM\Table(name: 'akeneo_taxon_attribute_translations')]
#[ORM\UniqueConstraint(name: 'attribute_translation', columns: ['translatable_id', 'locale'])]
class TaxonAttributeTranslation implements AttributeTranslationInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    /** @ORM\Column(name="name", type="string", length=255) */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    protected string $name = '';

    /** @ORM\Column(name="locale", type="string", length=255) */
    #[ORM\Column(name: 'locale', type: 'string', length: 255)]
    protected ?string $locale = null;

    /**
     * @ORM\ManyToOne(
     *     targetEntity="TaxonAttribute",
     *     inversedBy="translations",
     *     cascade={"persist", "remove"}
     * )
     *
     * @ORM\JoinColumn(nullable=true)
     */
    #[ORM\ManyToOne(
        targetEntity: 'TaxonAttribute',
        inversedBy: 'translations',
        cascade: ['persist', 'remove'],
    )]
    #[ORM\JoinColumn(nullable: true)]
    protected ?TranslatableInterface $translatable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        if (null === $name) {
            throw new \LogicException('Name should not be null');
        }

        $this->name = $name;
    }

    public function getTranslatable(): TranslatableInterface
    {
        $translatable = $this->translatable;

        // Return typehint should account for null value.
        Assert::notNull($translatable);

        return $translatable;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        if ($translatable === $this->translatable) {
            return;
        }

        $previousTranslatable = $this->translatable;
        $this->translatable = $translatable;

        if (null !== $previousTranslatable) {
            $previousTranslatable->removeTranslation($this);
        }

        if (null !== $translatable) {
            $translatable->addTranslation($this);
        }
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
