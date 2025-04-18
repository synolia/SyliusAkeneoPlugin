<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher\TaxonAttribute;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Throwable;

final class TaxonAttributeTypeMatcher
{
    public function __construct(
        /** @var iterable<TaxonAttributeTypeMatcherInterface> $typeMatchers */
        #[AutowireIterator(TaxonAttributeTypeMatcherInterface::TAG_ID)]
        private iterable $typeMatchers,
        private LoggerInterface $akeneoLogger,
    ) {
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
