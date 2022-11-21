<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\MetricTransformException;

final class MetricDataTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {
        $json = json_encode($value);

        if (false === $json) {
            throw new MetricTransformException('Could not transform metric array to json.');
        }

        return $json;
    }

    public function reverseTransform($value): ?array
    {
        return json_decode($value, true);
    }
}
