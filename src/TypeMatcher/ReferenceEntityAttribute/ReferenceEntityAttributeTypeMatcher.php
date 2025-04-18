<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\ReferenceEntityAttribute;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedReferenceEntityAttributeTypeException;

final class ReferenceEntityAttributeTypeMatcher
{
    public function __construct(
        /** @var iterable<ReferenceEntityAttributeTypeMatcherInterface> $typeMatchers */
        #[AutowireIterator(ReferenceEntityAttributeTypeMatcherInterface::TAG_ID)]
        private iterable $typeMatchers,
    ) {
    }

    public function match(string $type): ReferenceEntityAttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            if ($typeMatcher->support($type)) {
                return $typeMatcher;
            }
        }

        throw new UnsupportedReferenceEntityAttributeTypeException(sprintf('Unsupported Reference Entity Attribute Type "%s"', $type));
    }
}
