<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\TaxonAttribute;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

final class TaxonAttributeValueBuilder
{
    public function __construct(
        #[AutowireIterator(TaxonAttributeValueBuilderInterface::class)]
        private iterable $attributeValueBuilders,
        private LoggerInterface $akeneoLogger,
    ) {
    }

    /**
     * @return mixed|null
     */
    public function build(string $attributeCode, string $type, ?string $locale, ?string $scope, mixed $value)
    {
        /** @var TaxonAttributeValueBuilderInterface $attributeValueBuilder */
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeCode, $type)) {
                return $attributeValueBuilder->build($attributeCode, $locale, $scope, $value);
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function findBuilderByClassName(string $className)
    {
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if (!$attributeValueBuilder instanceof $className) {
                continue;
            }

            return $attributeValueBuilder;
        }

        return null;
    }

    public function hasSupportedBuilder(string $attributeCode): bool
    {
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            try {
                if ($attributeValueBuilder->support($attributeCode)) {
                    return true;
                }
            } catch (Throwable $throwable) {
                $this->akeneoLogger->error(sprintf(
                    'TaxonAttributeValueBuilder "%s" failed to execute method support() for attribute "%s"',
                    $attributeValueBuilder::class,
                    $attributeCode,
                ), ['exception' => $throwable]);

                return false;
            }
        }

        return false;
    }
}
