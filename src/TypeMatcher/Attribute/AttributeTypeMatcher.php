<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Throwable;

final class AttributeTypeMatcher
{
    public function __construct(
        /** @var iterable<AttributeTypeMatcherInterface> $typeMatchers */
        #[AutowireIterator(AttributeTypeMatcherInterface::class)]
        private iterable $typeMatchers,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    public function match(string $type): AttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            try {
                if ($typeMatcher->support($type)) {
                    return $typeMatcher;
                }
            } catch (Throwable $throwable) {
                $this->akeneoLogger->error(sprintf(
                    'AttributeTypeMatcher "%s" failed to execute method support() for attribute type "%s"',
                    $typeMatcher::class,
                    $type,
                ), ['exception' => $throwable]);

                throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
            }
        }

        throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
    }
}
