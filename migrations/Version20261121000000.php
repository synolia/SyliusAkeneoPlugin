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
    /** @var array<string, array<string, bool>> table => [column => nullable] */
    private const COLUMNS = [
        'akeneo_api_configuration_categories' => ['notImportCategories' => false, 'rootCategories' => false],
        'akeneo_api_configuration_product' => ['attributeMapping' => true],
        'akeneo_api_product_filters_rules' => ['locales' => false, 'excludeFamilies' => false],
        'akeneo_product_group' => ['variationAxes' => false, 'associations' => false],
        'akeneo_taxon_attributes' => ['configuration' => false],
        // akeneo_taxon_attribute_values.json_value is left out: it was already created as JSON.
    ];

    public function getDescription(): string
    {
        return 'Convert serialized PHP array columns to JSON';
    }

    public function preUp(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        // Data must be valid JSON before MySQL converts the column type.
        $this->convertData(self::serializedArrayToJson(...));
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->addSql($this->alterTable($table, $columns, 'JSON'));
        }
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        foreach (self::COLUMNS as $table => $columns) {
            $this->addSql($this->alterTable($table, $columns, 'LONGTEXT', '(DC2Type:array)'));
        }
    }

    public function postDown(Schema $schema): void
    {
        // Columns are LONGTEXT again, so they accept serialized values.
        $this->convertData(self::jsonToSerializedArray(...));
    }

    public static function serializedArrayToJson(string $value): string
    {
        $array = @unserialize($value, ['allowed_classes' => false]);

        if (!\is_array($array)) {
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

    public static function jsonToSerializedArray(string $value): string
    {
        $array = json_decode($value, true, 512, \JSON_THROW_ON_ERROR);

        if (!\is_array($array)) {
            throw new \UnexpectedValueException(sprintf('Value "%s" is not a JSON array.', $value));
        }

        return serialize($array);
    }

    /**
     * @param callable(string): string $converter
     */
    private function convertData(callable $converter): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                $rows = $this->connection->fetchAllAssociative(sprintf(
                    'SELECT id, `%s` AS value FROM `%s` WHERE `%s` IS NOT NULL',
                    $column,
                    $table,
                    $column,
                ));

                foreach ($rows as $row) {
                    $this->connection->update($table, [$column => $converter((string) $row['value'])], ['id' => $row['id']]);
                }
            }
        }
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
