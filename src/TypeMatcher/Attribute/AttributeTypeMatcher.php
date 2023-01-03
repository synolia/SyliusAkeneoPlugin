<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Throwable;

final class AttributeTypeMatcher
{
    /** @var array<AttributeTypeMatcherInterface> */
    private array $typeMatchers;

    public function __construct(private LoggerInterface $akeneoLogger)
    {
        $this->typeMatchers = [];
    }

    public function addTypeMatcher(AttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[$typeMatcher::class] = $typeMatcher;
    }

    public function match(string $type): AttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            try {
                if ($typeMatcher->support($type)) {
                    return $typeMatcher;
                }
            } catch (Throwable $throwable) {
                $this->akeneoLogger->critical(sprintf(
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
