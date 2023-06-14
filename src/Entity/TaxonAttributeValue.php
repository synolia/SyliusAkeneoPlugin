<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Webmozart\Assert\Assert;

/**
 * @ApiResource()
 *
 * @ORM\Entity()
 *
 * @ORM\Table(
 *     name="akeneo_taxon_attribute_values",
 *     uniqueConstraints={@ORM\UniqueConstraint(name="attribute_value", columns={"subject_id", "attribute_id", "locale_code"})}
 * )
 */
class TaxonAttributeValue implements TaxonAttributeValueInterface, ResourceInterface
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    protected ?int $id = null;

    /**
     * @ORM\ManyToOne(targetEntity="\Sylius\Component\Core\Model\TaxonInterface", inversedBy="attributes", cascade={"persist", "remove"})
     *
     * @ORM\JoinColumn(nullable=true)
     */
    #[ORM\ManyToOne(targetEntity: TaxonInterface::class, inversedBy: 'attributes')]
    #[ORM\JoinColumn(name: 'taxon_id', referencedColumnName: 'id')]
    protected ?TaxonInterface $subject;

    /**
     * @ORM\ManyToOne(targetEntity="TaxonAttribute", inversedBy="values")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    protected TaxonAttributeInterface $attribute;

    /** @ORM\Column(name="locale_code", type="string", length=255, nullable=true) */
    protected ?string $localeCode;

    /** @ORM\Column(name="text_value", type="text", nullable=true) */
    private ?string $text;

    /** @ORM\Column(name="boolean_value", type="boolean", nullable=true) */
    private ?bool $boolean;

    /** @ORM\Column(name="integer_value", type="integer", nullable=true) */
    private ?int $integer;

    /** @ORM\Column(name="float_value", type="float", nullable=true) */
    private ?float $float;

    /** @ORM\Column(name="datetime_value", type="datetime", nullable=true) */
    private ?\DateTimeInterface $datetime;

    /** @ORM\Column(name="date_value", type="date", nullable=true) */
    private ?DateTimeInterface $date;

    /** @ORM\Column(name="json_value", type="json", nullable=true) */
    private ?array $json;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubject(): ?TaxonInterface
    {
        return $this->subject;
    }

    public function setSubject(?TaxonInterface $subject): void
    {
        $this->subject = $subject;
    }

    public function getAttribute(): ?TaxonAttributeInterface
    {
        return $this->attribute;
    }

    public function setAttribute(?TaxonAttributeInterface $attribute): void
    {
        $this->attribute = $attribute;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): void
    {
        $this->localeCode = $localeCode;
    }

    public function getValue()
    {
        if (null === $this->attribute) {
            return null;
        }

        $getter = 'get' . $this->attribute->getStorageType();

        return $this->$getter();
    }

    public function setValue($value): void
    {
        $this->assertAttributeIsSet();

        $setter = 'set' . $this->attribute->getStorageType();

        $this->$setter($value);
    }

    public function getCode(): ?string
    {
        $this->assertAttributeIsSet();

        return $this->attribute->getCode();
    }

    public function getName(): ?string
    {
        $this->assertAttributeIsSet();

        return $this->attribute->getName();
    }

    public function getType(): ?string
    {
        $this->assertAttributeIsSet();

        return $this->attribute->getType();
    }

    protected function getBoolean(): ?bool
    {
        return $this->boolean;
    }

    protected function setBoolean(?bool $boolean): void
    {
        $this->boolean = $boolean;
    }

    protected function getText(): ?string
    {
        return $this->text;
    }

    protected function setText(?string $text): void
    {
        $this->text = $text;
    }

    protected function getInteger(): ?int
    {
        return $this->integer;
    }

    protected function setInteger(?int $integer): void
    {
        $this->integer = $integer;
    }

    protected function getFloat(): ?float
    {
        return $this->float;
    }

    protected function setFloat(?float $float): void
    {
        $this->float = $float;
    }

    protected function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    /**
     * @param \DateTimeInterface $datetime
     */
    protected function setDatetime(?\DateTimeInterface $datetime): void
    {
        $this->datetime = $datetime;
    }

    protected function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    protected function setDate(?\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    protected function getJson(): ?array
    {
        return $this->json;
    }

    protected function setJson(?array $json): void
    {
        $this->json = $json;
    }

    /**
     * @throws \BadMethodCallException
     */
    protected function assertAttributeIsSet(): void
    {
        if (null === $this->attribute) {
            throw new \BadMethodCallException('The attribute is undefined, so you cannot access proxy methods.');
        }
    }

    public function getTaxon(): ?TaxonInterface
    {
        $subject = $this->getSubject();

        Assert::nullOrIsInstanceOf($subject, TaxonInterface::class);

        return $subject;
    }

    public function setTaxon(?TaxonInterface $taxon): void
    {
        $this->setSubject($taxon);
    }

    public function __toString(): string
    {
        return $this->getCode();
    }
}
