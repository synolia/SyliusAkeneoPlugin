<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\MetricTransformException;

final class MetricDataTransformer implements DataTransformerInterface
{
    /**
     * @throws MetricTransformException
     */
    public function transform($value): string
    {
        if (!is_array($value)) {
            throw new MetricTransformException('Could not transform data to json.');
        }

        $json = json_encode($value);

        if (false === $json) {
            throw new MetricTransformException('Could not transform metric array to json.');
        }

        return $json;
    }

    /**
     * @throws MetricTransformException
     */
    public function reverseTransform($value): ?array
    {
        if (!is_string($value)) {
            throw new MetricTransformException('Could not transform data to json.');
        }

        $array = \json_decode($value, true);

        if ($array !== null && !is_array($array)) {
            throw new MetricTransformException();
        }

        return $array;
    }
}
