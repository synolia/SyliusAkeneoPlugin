<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedReferenceEntityAttributeTypeException;
use Webmozart\Assert\Assert;

final class ReferenceEntityAttributeTypeMatcher
{
    /** @var array<ReferenceEntityAttributeTypeMatcherInterface> */
    private ?array $typeMatchers = null;

    public function addTypeMatcher(ReferenceEntityAttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[\get_class($typeMatcher)] = $typeMatcher;
    }

    public function match(string $type): ReferenceEntityAttributeTypeMatcherInterface
    {
        Assert::isIterable($this->typeMatchers);

        foreach ($this->typeMatchers as $typeMatcher) {
            $typeMatcherSupport = $typeMatcher->support($type);
            if ($typeMatcherSupport) {
                return $typeMatcher;
            }
        }

        throw new UnsupportedReferenceEntityAttributeTypeException(\sprintf('Unsupported Reference Entity Attribute Type "%s"', $type));
    }
}
