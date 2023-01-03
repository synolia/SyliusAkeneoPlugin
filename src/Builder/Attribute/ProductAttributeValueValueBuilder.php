<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Builder\Attribute;

use Psr\Log\LoggerInterface;
use Throwable;

final class ProductAttributeValueValueBuilder
{
    private array $attributeValueBuilders;

    public function __construct(private LoggerInterface $akeneoLogger)
    {
        $this->attributeValueBuilders = [];
    }

    public function addBuilder(ProductAttributeValueValueBuilderInterface $attributeValueBuilder): void
    {
        $this->attributeValueBuilders[$attributeValueBuilder::class] = $attributeValueBuilder;
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
                $this->akeneoLogger->critical(sprintf(
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
