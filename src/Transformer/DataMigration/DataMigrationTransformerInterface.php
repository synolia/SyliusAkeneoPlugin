<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\DataMigration;

interface DataMigrationTransformerInterface
{
    public const TAG_ID = 'sylius.akeneo.data_migration_transformer';

    public function support(string $fromType, string $toType): bool;
}
