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
    public function transform(mixed $value): string
    {
        if (null === $value) {
            return '';
        }

        if (!is_array($value)) {
            throw new MetricTransformException('Could not transform data to json.');
        }

        return json_encode($value, \JSON_THROW_ON_ERROR);
    }

    /**
     * @throws MetricTransformException
     */
    public function reverseTransform(mixed $value): ?array
    {
        if (null === $value) {
            return null;
        }

        if (!is_string($value)) {
            throw new MetricTransformException('Could not transform data to json.');
        }

        $array = \json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if ($array !== null && !is_array($array)) {
            throw new MetricTransformException();
        }

        return $array;
    }
}
