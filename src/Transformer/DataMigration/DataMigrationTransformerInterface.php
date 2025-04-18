<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Transformer\DataMigration;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(name: self::TAG_ID)]
interface DataMigrationTransformerInterface
{
    public const TAG_ID = 'sylius.akeneo.data_migration_transformer';

    public function support(string $fromType, string $toType): bool;

    public function transform(mixed $value): array;
}
