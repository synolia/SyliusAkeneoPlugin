<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Throwable;

final class TaxonAttributeTypeMatcher
{
    /** @var array<TaxonAttributeTypeMatcherInterface> */
    private array $typeMatchers;

    public function __construct(private LoggerInterface $akeneoLogger)
    {
        $this->typeMatchers = [];
    }

    public function addTypeMatcher(TaxonAttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[$typeMatcher::class] = $typeMatcher;
    }

    /**
     * @throws UnsupportedAttributeTypeException
     */
    public function match(string $type): TaxonAttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            try {
                if ($typeMatcher->support($type)) {
                    return $typeMatcher;
                }
            } catch (Throwable $throwable) {
                $this->akeneoLogger->critical(sprintf(
                    'TaxonAttributeTypeMatcher "%s" failed to execute method support() for attribute type "%s"',
                    $typeMatcher::class,
                    $type,
                ), ['exception' => $throwable]);

                throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
            }
        }

        throw new UnsupportedAttributeTypeException(sprintf('Unsupported Attribute Type "%s"', $type));
    }
}
