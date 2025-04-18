<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use JsonException;
use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\AssetTransformException;

final class AssetEntityDataTransformer implements DataTransformerInterface
{
    /**
     * @throws AssetTransformException
     */
    public function transform(mixed $value): string
    {
        try {
            return json_encode($value, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new AssetTransformException('Could not transform asset array to json.', 0, $exception);
        }
    }

    public function reverseTransform(mixed $value): ?array
    {
        return json_decode((string) $value, true, 512, \JSON_THROW_ON_ERROR);
    }
}
