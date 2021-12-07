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

    private LoggerInterface $akeneoLogger;

    public function __construct(LoggerInterface $akeneoLogger)
    {
        $this->akeneoLogger = $akeneoLogger;
        $this->typeMatchers = [];
    }

    public function addTypeMatcher(AttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[\get_class($typeMatcher)] = $typeMatcher;
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
                    \get_class($typeMatcher),
                    $type
                ), ['exception' => $throwable]);

                throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
            }
        }

        throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
    }
}
