<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Attribute\Model\AttributeTranslationInterface;
use Sylius\Component\Resource\Model\AbstractTranslation;
use Sylius\Component\Resource\Model\TranslatableInterface;

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
class TaxonAttributeTranslation extends AbstractTranslation implements AttributeTranslationInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    protected ?int $id = null;

    /** @ORM\Column(name="name", type="string", length=255) */
    protected string $name = '';

    /**
     * @ORM\ManyToOne(
     *     targetEntity="TaxonAttribute",
     *     inversedBy="translations",
     *     cascade={"persist", "remove"}
     * )
     *
     * @ORM\JoinColumn(nullable=true)
     */
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

    public function __toString(): string
    {
        return (string) $this->getName();
    }
}
