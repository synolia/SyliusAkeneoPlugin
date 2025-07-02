<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\AssetAttributeTypeMatcherInterface;

final class AssetAttributeTypeMatcherProvider implements AssetAttributeTypeMatcherProviderInterface
{
    public function __construct(
        /** @var iterable<AssetAttributeTypeMatcherInterface> $typeMatchers */
        #[AutowireIterator(AssetAttributeTypeMatcherInterface::class)]
        private iterable $typeMatchers,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    /**
     * @throws UnsupportedAttributeTypeException
     */
    public function match(string $type): AssetAttributeTypeMatcherInterface
    {
        foreach ($this->typeMatchers as $typeMatcher) {
            try {
                if ($typeMatcher->support($type)) {
                    return $typeMatcher;
                }
            } catch (\Throwable $throwable) {
                $this->akeneoLogger->error(\sprintf(
                    'AttributeTypeMatcher "%s" failed to execute method support() for attribute type "%s"',
                    $typeMatcher::class,
                    $type,
                ), ['exception' => $throwable]);

                throw new UnsupportedAttributeTypeException(\sprintf('Unsupported Asset Attribute Type "%s"', $type));
            }
        }

        throw new UnsupportedAttributeTypeException(\sprintf('Unsupported Asset Attribute Type "%s"', $type));
    }
}
