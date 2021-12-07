<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\ReferenceEntityTransformException;

final class ReferenceEntityDataTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {
        $json = json_encode($value);

        if (false === $json) {
            throw new ReferenceEntityTransformException('Could not transform reference entity array to json.');
        }

        return $json;
    }

    public function reverseTransform($value): ?array
    {
        return json_decode($value, true);
    }
}
