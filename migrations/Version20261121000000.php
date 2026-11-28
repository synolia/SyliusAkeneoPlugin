<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * DBAL 4 removed the "array" type (serialized PHP): these columns are converted to JSON.
 */
final class Version20261121000000 extends AbstractMigration
{
    /** Rows converted by each queued UPDATE: keeps the queue small on large catalogs. */
    public const BATCH_SIZE = 500;

    /** @var array<string, array<string, bool>> table => [column => nullable] */
    private const COLUMNS = [
        'akeneo_api_configuration_categories' => ['notImportCategories' => false, 'rootCategories' => false],
        'akeneo_api_configuration_product' => ['attributeMapping' => true],
        'akeneo_api_product_filters_rules' => ['locales' => false, 'excludeFamilies' => false],
        'akeneo_product_group' => ['variationAxes' => false, 'associations' => false],
        'akeneo_taxon_attributes' => ['configuration' => false],
        // Created as JSON by Version20230425081120, but LONGTEXT on schemas built from the former ARRAY mapping.
        'akeneo_taxon_attribute_values' => ['json_value' => true],
    ];

    public function getDescription(): string
    {
        return 'Convert serialized PHP array columns to JSON';
    }

    /**
     * Data is converted with queued SQL (not written right away) so that --dry-run changes nothing,
     * and table by table so that a failed ALTER leaves the other tables consistent.
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        foreach (self::COLUMNS as $table => $columns) {
            // Data must be valid JSON before MySQL converts the column type.
            $this->queueDataConversion($table, $columns, self::serializedArrayToJson(...), '[]');
            $this->addSql($this->alterTable($table, $columns, 'JSON'));
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        foreach (self::COLUMNS as $table => $columns) {
            $this->addSql($this->alterTable($table, $columns, 'LONGTEXT', '(DC2Type:array)'));
            // Queued after the ALTER: only LONGTEXT columns accept serialized values.
            $this->queueDataConversion($table, $columns, self::jsonToSerializedArray(...), serialize([]));
        }
    }

    /**
     * Values already stored as JSON are kept, so the migration can be run again after a failure.
     */
    public static function serializedArrayToJson(string $value): ?string
    {
        // DBAL 3 ArrayType stores a PHP null as "N;" instead of SQL NULL.
        if ('N;' === $value) {
            return null;
        }

        $array = @unserialize($value, ['allowed_classes' => false]);

        if (!\is_array($array)) {
            if (self::isJsonArray($value)) {
                return $value;
            }

            throw new \UnexpectedValueException(sprintf('Value "%s" is not a serialized array.', $value));
        }

        array_walk_recursive($array, static function (mixed $item) use ($value): void {
            if (\is_object($item)) {
                throw new \UnexpectedValueException(sprintf('Value "%s" contains an object and cannot be converted to JSON.', $value));
            }
        });

        // Same flags as Doctrine\DBAL\Types\JsonType
        return json_encode($array, \JSON_THROW_ON_ERROR | \JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Values already stored as serialized arrays are kept, so the migration can be reverted again after a failure.
     */
    public static function jsonToSerializedArray(string $value): ?string
    {
        if (!self::isValidJson($value)) {
            if (\is_array(@unserialize($value, ['allowed_classes' => false]))) {
                return $value;
            }

            throw new \UnexpectedValueException(sprintf('Value "%s" is not a JSON array.', $value));
        }

        $array = json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if (null === $array) {
            return null;
        }

        if (!\is_array($array)) {
            throw new \UnexpectedValueException(sprintf('Value "%s" is not a JSON array.', $value));
        }

        return serialize($array);
    }

    /**
     * json_validate() requires PHP 8.3.
     */
    private static function isValidJson(string $value): bool
    {
        json_decode($value);

        return \JSON_ERROR_NONE === json_last_error();
    }

    private static function isJsonArray(string $value): bool
    {
        return self::isValidJson($value) && \is_array(json_decode($value, true));
    }

    /**
     * @param array<string, bool> $columns
     * @param callable(string): ?string $converter
     */
    private function queueDataConversion(string $table, array $columns, callable $converter, string $emptyArray): void
    {
        foreach ($columns as $column => $nullable) {
            $rows = $this->connection->iterateAssociative(sprintf(
                'SELECT id, `%s` AS value FROM `%s` WHERE `%s` IS NOT NULL',
                $column,
                $table,
                $column,
            ));

            $batch = [];
            foreach ($rows as $row) {
                $value = (string) $row['value'];
                $converted = $converter($value) ?? ($nullable ? null : $emptyArray);

                if ($converted === $value) {
                    continue;
                }

                $batch[$row['id']] = $converted;

                if (\count($batch) === self::BATCH_SIZE) {
                    $this->queueBatchUpdate($table, $column, $batch);
                    $batch = [];
                }
            }

            if ([] !== $batch) {
                $this->queueBatchUpdate($table, $column, $batch);
            }
        }
    }

    /**
     * @param array<int|string, ?string> $values id => value
     */
    private function queueBatchUpdate(string $table, string $column, array $values): void
    {
        $cases = [];
        foreach ($values as $id => $value) {
            array_push($cases, $id, $value);
        }

        $this->addSql(
            sprintf(
                'UPDATE `%s` SET `%s` = CASE id %s END WHERE id IN (%s)',
                $table,
                $column,
                implode(' ', array_fill(0, \count($values), 'WHEN ? THEN ?')),
                implode(', ', array_fill(0, \count($values), '?')),
            ),
            [...$cases, ...array_keys($values)],
        );
    }

    /**
     * @param array<string, bool> $columns
     */
    private function alterTable(string $table, array $columns, string $type, ?string $comment = null): string
    {
        $changes = [];
        foreach ($columns as $column => $nullable) {
            $changes[] = sprintf(
                'CHANGE `%1$s` `%1$s` %2$s %3$s%4$s',
                $column,
                $type,
                $nullable ? 'DEFAULT NULL' : 'NOT NULL',
                null === $comment ? '' : sprintf(' COMMENT \'%s\'', $comment),
            );
        }

        return sprintf('ALTER TABLE `%s` %s', $table, implode(', ', $changes));
    }
}
