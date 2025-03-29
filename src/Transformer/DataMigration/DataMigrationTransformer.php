<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\DataMigration;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Synolia\SyliusAkeneoPlugin\Exceptions\Transformer\DataMigration\NoDataMigrationTransformerFoundException;
use Throwable;

final class DataMigrationTransformer
{
    public function __construct(
        #[AutowireIterator(DataMigrationTransformerInterface::class)]
        private iterable $dataMigrationTransformers
    ) {
    }

    public function addDataMigrationTransformer(DataMigrationTransformerInterface $dataMigrationTransformer): void
    {
        $this->dataMigrationTransformers[$dataMigrationTransformer::class] = $dataMigrationTransformer;
    }

    /**
     * @return mixed
     */
    public function transform(string $fromType, string $toType, mixed $value)
    {
        foreach ($this->dataMigrationTransformers as $dataMigrationTransformer) {
            try {
                if ($dataMigrationTransformer->support($fromType, $toType)) {
                    /** @phpstan-ignore-next-line */
                    return $dataMigrationTransformer->transform($value);
                }
            } catch (Throwable) {
                throw new NoDataMigrationTransformerFoundException();
            }
        }

        throw new NoDataMigrationTransformerFoundException();
    }
}
