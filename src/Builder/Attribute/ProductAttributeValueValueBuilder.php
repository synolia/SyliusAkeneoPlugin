<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Throwable;

final class ProductAttributeValueValueBuilder
{
    public function __construct(
        private LoggerInterface $akeneoLogger,
        /** @var iterable<ProductAttributeValueValueBuilderInterface> $attributeValueBuilders */
        #[AutowireIterator(ProductAttributeValueValueBuilderInterface::TAG_ID)]
        private iterable $attributeValueBuilders,
    ) {
    }

    /**
     * @return mixed|null
     */
    public function build(string $attributeCode, ?string $locale, ?string $scope, mixed $value)
    {
        foreach ($this->attributeValueBuilders as $attributeValueBuilder) {
            if ($attributeValueBuilder->support($attributeCode)) {
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
                    'AttributeValueBuilder "%s" failed to execute method support() for attribute "%s"',
                    $attributeValueBuilder::class,
                    $attributeCode,
                ), ['exception' => $throwable]);

                return false;
            }
        }

        return false;
    }
}
