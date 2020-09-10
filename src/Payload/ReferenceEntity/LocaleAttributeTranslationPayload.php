<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class LocaleAttributeTranslationPayload extends AbstractPayload
{
    /** @var ProductInterface */
    private $product;

    /** @var AttributeInterface */
    private $attribute;

    /** @var array */
    private $translations;

    /** @var array */
    private $translation;

    /** @var string */
    private $referenceEntityCode;

    /** @var string */
    private $attributeCode;

    /** @var string */
    private $scope;

    public function getProduct(): ProductInterface
    {
        return $this->product;
    }

    public function setProduct(ProductInterface $product): self
    {
        $this->product = $product;

        return $this;
    }

    public function getAttribute(): AttributeInterface
    {
        return $this->attribute;
    }

    public function setAttribute(AttributeInterface $attribute): self
    {
        $this->attribute = $attribute;

        return $this;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function setTranslations(array $translations): self
    {
        $this->translations = $translations;

        return $this;
    }

    public function getTranslation(): array
    {
        return $this->translation;
    }

    public function setTranslation(array $translation): self
    {
        $this->translation = $translation;

        return $this;
    }

    public function getAttributeCode(): string
    {
        return $this->attributeCode;
    }

    public function setAttributeCode(string $attributeCode): self
    {
        $this->attributeCode = $attributeCode;

        return $this;
    }

    public function getScope(): string
    {
        return $this->scope;
    }

    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    public function getReferenceEntityCode(): string
    {
        return $this->referenceEntityCode;
    }

    public function setReferenceEntityCode(string $referenceEntityCode): self
    {
        $this->referenceEntityCode = $referenceEntityCode;

        return $this;
    }
}
