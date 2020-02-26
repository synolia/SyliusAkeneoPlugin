<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;

final class AttributeTypeMatcher
{
    /** @var array<AttributeTypeMatcherInterface> */
    private $typeMatchers;

    public function addTypeMatcher(AttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[\get_class($typeMatcher)] = $typeMatcher;
    }

    public function match(string $type): AttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            if ($typeMatcher->support($type)) {
                return $typeMatcher;
            }
        }

        throw new UnsupportedAttributeTypeException('Unsupported Attribute Type');
    }
}
