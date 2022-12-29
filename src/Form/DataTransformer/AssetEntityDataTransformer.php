<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\AssetTransformException;

final class AssetEntityDataTransformer implements DataTransformerInterface
{
    /**
     * @throws AssetTransformException
     */
    public function transform(mixed $value): string
    {
        return json_encode($value, \JSON_THROW_ON_ERROR);
    }

    public function reverseTransform(mixed $value): ?array
    {
        return json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
    }
}
