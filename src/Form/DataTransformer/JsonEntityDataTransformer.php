<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Form\DataTransformer;

use JsonException;
use Symfony\Component\Form\DataTransformerInterface;
use Synolia\SyliusAkeneoPlugin\Exceptions\DataTransformer\JsonTransformException;
use Webmozart\Assert\Assert;

final class JsonEntityDataTransformer implements DataTransformerInterface
{
    /**
     * @throws JsonTransformException
     */
    public function transform(mixed $value): string
    {
        try {
            return json_encode($value, \JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonTransformException('Could not transform asset array to json.', 0, $exception);
        }
    }

    /**
     * @param string $value
     *
     * @throws JsonException
     */
    public function reverseTransform($value): ?array
    {
        Assert::string($value);

        /** @phpstan-ignore-next-line */
        return json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
    }
}
