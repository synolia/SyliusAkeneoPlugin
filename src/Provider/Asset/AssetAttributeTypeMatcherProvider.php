<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Asset;

use Psr\Log\LoggerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Asset\Attribute\AssetAttributeTypeMatcherInterface;

final class AssetAttributeTypeMatcherProvider implements AssetAttributeTypeMatcherProviderInterface
{
    /** @var array<AssetAttributeTypeMatcherInterface> */
    private array $typeMatchers;

    public function __construct(private LoggerInterface $akeneoLogger)
    {
    }

    public function addTypeMatcher(AssetAttributeTypeMatcherInterface $typeMatcher): void
    {
        $this->typeMatchers[$typeMatcher::class] = $typeMatcher;
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
