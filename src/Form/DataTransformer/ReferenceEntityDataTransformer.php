<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use JsonException;
use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\ReferenceEntityTransformException;

final class ReferenceEntityDataTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        try {
            return json_encode($value, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ReferenceEntityTransformException('Could not transform reference entity array to json.', 0, $exception);
        }
    }

    public function reverseTransform(mixed $value): ?array
    {
        return json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
    }
}
