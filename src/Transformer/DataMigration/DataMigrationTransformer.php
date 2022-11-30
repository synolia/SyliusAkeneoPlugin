<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\DataMigration;

use Synolia\SyliusAkeneoPlugin\Exceptions\Transformer\DataMigration\NoDataMigrationTransformerFoundException;
use Throwable;

final class DataMigrationTransformer
{
    /** @var array<DataMigrationTransformerInterface> */
    private array $dataMigrationTransformers = [];

    public function addDataMigrationTransformer(DataMigrationTransformerInterface $dataMigrationTransformer): void
    {
        $this->dataMigrationTransformers[\get_class($dataMigrationTransformer)] = $dataMigrationTransformer;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function transform(string $fromType, string $toType, $value)
    {
        foreach ($this->dataMigrationTransformers as $dataMigrationTransformer) {
            try {
                if ($dataMigrationTransformer->support($fromType, $toType)) {
                    /** @phpstan-ignore-next-line */
                    return $dataMigrationTransformer->transform($value);
                }
            } catch (Throwable $throwable) {
                throw new NoDataMigrationTransformerFoundException();
            }
        }

        throw new NoDataMigrationTransformerFoundException();
    }
}
