<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Webmozart\Assert\Assert;

final class AttributeTypeMatcher
{
    /** @var array<AttributeTypeMatcherInterface> */
    private ?array $typeMatchers = null;

    public function addTypeMatcher(AttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[\get_class($typeMatcher)] = $typeMatcher;
    }

    public function match(string $type): AttributeTypeMatcherInterface
    {
        Assert::isIterable($this->typeMatchers);

        foreach ($this->typeMatchers as $typeMatcher) {
            if ($typeMatcher->support($type)) {
                return $typeMatcher;
            }
        }

        throw new UnsupportedAttributeTypeException(\sprintf('Unsupported Attribute Type "%s"', $type));
    }
}
