<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Entity;

use ApiPlatform\Metadata\ApiResource;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Resource\Model\ResourceInterface;
use Synolia\SyliusAkeneoPlugin\Component\TaxonAttribute\Model\TaxonAttributeSubjectInterface;
use Webmozart\Assert\Assert;

#[ApiResource]
#[ORM\Entity]
#[ORM\Table(name: 'akeneo_taxon_attribute_values')]
#[ORM\UniqueConstraint(name: 'attribute_value', columns: ['subject_id', 'attribute_id', 'locale_code'])]
class TaxonAttributeValue implements TaxonAttributeValueInterface, ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TaxonInterface::class, cascade: ['persist', 'remove'], inversedBy: 'attributes')]
    #[ORM\JoinColumn(nullable: true)]
    protected ?TaxonInterface $subject;

    #[ORM\ManyToOne(targetEntity: 'TaxonAttribute', inversedBy: 'values')]
    #[ORM\JoinColumn(nullable: false)]
    protected TaxonAttributeInterface $attribute;

    #[ORM\Column(name: 'locale_code', type: Types::STRING, length: 255, nullable: true)]
    protected ?string $localeCode;

    #[ORM\Column(name: 'text_value', type: Types::TEXT, nullable: true)]
    private ?string $text;

    #[ORM\Column(name: 'boolean_value', type: Types::BOOLEAN, nullable: true)]
    private ?bool $boolean;

    #[ORM\Column(name: 'integer_value', type: Types::INTEGER, nullable: true)]
    private ?int $integer;

    #[ORM\Column(name: 'float_value', type: Types::FLOAT, nullable: true)]
    private ?float $float;

    #[ORM\Column(name: 'datetime_value', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $datetime;

    #[ORM\Column(name: 'date_value', type: Types::DATE_MUTABLE, nullable: true)]
    private ?DateTimeInterface $date;

    #[ORM\Column(name: 'json_value', type: Types::ARRAY, nullable: true)]
    private ?array $json;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSubject(): ?TaxonInterface
    {
        return $this->subject;
    }

    public function setSubject(?TaxonInterface $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getAttribute(): ?TaxonAttributeInterface
    {
        return $this->attribute;
    }

    public function setAttribute(?TaxonAttributeInterface $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getLocaleCode(): ?string
    {
        return $this->localeCode;
    }

    public function setLocaleCode(?string $localeCode): self
    {
        $this->localeCode = $localeCode;

        return $this;
    }

    public function getValue()
    {
        if (null === $this->attribute) {
            return null;
        }

        $getter = 'get' . $this->attribute->getStorageType();

        return $this->$getter();
    }

    public function setValue($value): self
    {
        $this->assertAttributeIsSet();

        $setter = 'set' . $this->attribute->getStorageType();

        $this->$setter($value);

        return $this;
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

    protected function setBoolean(?bool $boolean): self
    {
        $this->boolean = $boolean;

        return $this;
    }

    protected function getText(): ?string
    {
        return $this->text;
    }

    protected function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    protected function getInteger(): ?int
    {
        return $this->integer;
    }

    protected function setInteger(?int $integer): self
    {
        $this->integer = $integer;

        return $this;
    }

    protected function getFloat(): ?float
    {
        return $this->float;
    }

    protected function setFloat(?float $float): self
    {
        $this->float = $float;

        return $this;
    }

    protected function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    protected function setDatetime(?\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    protected function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    protected function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    protected function getJson(): ?array
    {
        return $this->json;
    }

    protected function setJson(?array $json): self
    {
        $this->json = $json;

        return $this;
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

    public function setTaxon(?TaxonInterface $taxon): self
    {
        $this->setSubject($taxon);

        if ($taxon instanceof TaxonAttributeSubjectInterface) {
            $taxon->addAttribute($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getCode();
    }
}
