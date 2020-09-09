<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedReferenceEntityAttributeTypeException;

final class ReferenceEntityAttributeTypeMatcher
{
    /** @var array<ReferenceEntityAttributeTypeMatcherInterface> */
    private $typeMatchers;

    public function addTypeMatcher(ReferenceEntityAttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[\get_class($typeMatcher)] = $typeMatcher;
    }

    public function match(string $type): ReferenceEntityAttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            if ($typeMatcher->support($type)) {
                return $typeMatcher;
            }
        }

        throw new UnsupportedReferenceEntityAttributeTypeException(\sprintf('Unsupported Reference Entity Attribute Type "%s"', $type));
    }
}
