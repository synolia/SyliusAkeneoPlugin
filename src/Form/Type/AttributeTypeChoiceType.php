<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AttributeTypeChoiceType extends AbstractType
{
    /** @var array */
    private $attributeTypes;

    public function __construct(array $attributeTypes)
    {
        $this->attributeTypes = $attributeTypes;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choices', array_merge($this->attributeTypes, ['multiselect' => 'multiselect']));
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
