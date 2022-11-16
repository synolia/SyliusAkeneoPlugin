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
    public function transform($value): string
    {
        $json = json_encode($value);

        if (false === $json) {
            throw new AssetTransformException('Could not transform asset array to json.');
        }

        return $json;
    }

    public function reverseTransform($value): ?array
    {
        return json_decode($value, true);
    }
}
