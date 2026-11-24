<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Migrations;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Query\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Synolia\SyliusAkeneoPlugin\Migrations\Version20261121000000;

/**
 * @internal
 *
 * @coversNothing
 */
final class Version20261121000000Test extends TestCase
{
    private const TABLES = [
        'akeneo_api_configuration_categories' => ['notImportCategories', 'rootCategories'],
        'akeneo_api_configuration_product' => ['attributeMapping'],
        'akeneo_api_product_filters_rules' => ['locales', 'excludeFamilies'],
        'akeneo_product_group' => ['variationAxes', 'associations'],
        'akeneo_taxon_attributes' => ['configuration'],
        'akeneo_taxon_attribute_values' => ['json_value'],
    ];

    /**
     * @dataProvider arrayProvider
     */
    public function testSerializedArrayIsConvertedToJson(array $value, string $json): void
    {
        self::assertSame($json, Version20261121000000::serializedArrayToJson(serialize($value)));
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testJsonIsConvertedBackToSerializedArray(array $value, string $json): void
    {
        self::assertSame(serialize($value), Version20261121000000::jsonToSerializedArray($json));
    }

    public function testSerializedNullIsConvertedToNull(): void
    {
        // DBAL 3 ArrayType stores a PHP null as "N;" instead of SQL NULL.
        self::assertNull(Version20261121000000::serializedArrayToJson('N;'));
    }

    public function testJsonNullIsConvertedBackToNull(): void
    {
        self::assertNull(Version20261121000000::jsonToSerializedArray('null'));
    }

    /**
     * @dataProvider jsonProvider
     */
    public function testValueAlreadyConvertedToJsonIsKept(string $json): void
    {
        self::assertSame($json, Version20261121000000::serializedArrayToJson($json));
    }

    /**
     * @dataProvider arrayProvider
     */
    public function testValueAlreadyConvertedBackToSerializedArrayIsKept(array $value): void
    {
        self::assertSame(serialize($value), Version20261121000000::jsonToSerializedArray(serialize($value)));
    }

    public function testSerializedObjectsAreRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Version20261121000000::serializedArrayToJson(serialize([new \ArrayObject()]));
    }

    public function testInvalidSerializedValueIsRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Version20261121000000::serializedArrayToJson('not serialized');
    }

    public function testJsonScalarIsRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Version20261121000000::serializedArrayToJson('"not an array"');
    }

    public function testInvalidJsonValueIsRejected(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        Version20261121000000::jsonToSerializedArray('not json');
    }

    public function testUpQueuesDataConversionBeforeEachAlterWithoutWritingData(): void
    {
        $connection = self::createConnection();
        $connection->insert('akeneo_api_product_filters_rules', ['id' => 1, 'locales' => serialize(['en_US']), 'excludeFamilies' => serialize([])]);
        $connection->insert('akeneo_api_configuration_product', ['id' => 1, 'attributeMapping' => 'N;']);
        $connection->insert('akeneo_taxon_attribute_values', ['id' => 1, 'json_value' => '{"a":1}']);

        $migration = new Version20261121000000($connection, new NullLogger());
        $migration->up(new Schema());
        $statements = self::statements($migration);

        $locales = self::indexOf($statements, self::conversionSql('akeneo_api_product_filters_rules', 'locales', 1), [1, '["en_US"]', 1]);
        self::assertLessThan(self::indexOfAlter($statements, 'akeneo_api_product_filters_rules'), $locales);
        self::assertGreaterThan(self::indexOfAlter($statements, 'akeneo_api_configuration_product'), $locales);
        self::indexOf($statements, self::conversionSql('akeneo_api_product_filters_rules', 'excludeFamilies', 1), [1, '[]', 1]);
        self::indexOf($statements, self::conversionSql('akeneo_api_configuration_product', 'attributeMapping', 1), [1, null, 1]);
        self::assertStringContainsString('`json_value` `json_value` JSON DEFAULT NULL', $statements[self::indexOfAlter($statements, 'akeneo_taxon_attribute_values')]['sql']);
        self::assertSame([], array_filter($statements, static fn (array $statement): bool => str_contains($statement['sql'], 'SET `json_value`')), 'Values already stored as JSON must not be updated.');

        // Nothing is written before the queued SQL runs, so --dry-run leaves the data untouched.
        self::assertSame(serialize(['en_US']), $connection->fetchOne('SELECT locales FROM akeneo_api_product_filters_rules'));
        self::assertSame('N;', $connection->fetchOne('SELECT attributeMapping FROM akeneo_api_configuration_product'));
    }

    public function testDownQueuesDataConversionAfterEachAlterWithoutWritingData(): void
    {
        $connection = self::createConnection();
        $connection->insert('akeneo_api_product_filters_rules', ['id' => 1, 'locales' => '["en_US"]', 'excludeFamilies' => '[]']);

        $migration = new Version20261121000000($connection, new NullLogger());
        $migration->down(new Schema());
        $statements = self::statements($migration);

        $locales = self::indexOf($statements, self::conversionSql('akeneo_api_product_filters_rules', 'locales', 1), [1, serialize(['en_US']), 1]);
        self::assertGreaterThan(self::indexOfAlter($statements, 'akeneo_api_product_filters_rules'), $locales);
        self::assertSame('["en_US"]', $connection->fetchOne('SELECT locales FROM akeneo_api_product_filters_rules'));
    }

    public function testDataConversionIsQueuedInBoundedBatches(): void
    {
        $connection = self::createConnection();
        $rowCount = Version20261121000000::BATCH_SIZE + 1;
        for ($id = 1; $id <= $rowCount; ++$id) {
            $connection->insert('akeneo_product_group', ['id' => $id, 'variationAxes' => serialize(['size']), 'associations' => serialize([])]);
        }

        $migration = new Version20261121000000($connection, new NullLogger());
        $migration->up(new Schema());
        $updates = array_values(array_filter(
            self::statements($migration),
            static fn (array $statement): bool => str_starts_with($statement['sql'], 'UPDATE `akeneo_product_group` SET `variationAxes`'),
        ));

        self::assertCount(2, $updates);
        self::assertSame(self::conversionSql('akeneo_product_group', 'variationAxes', Version20261121000000::BATCH_SIZE), $updates[0]['sql']);
        self::assertSame(self::conversionSql('akeneo_product_group', 'variationAxes', 1), $updates[1]['sql']);
        self::assertSame([$rowCount, '["size"]', $rowCount], $updates[1]['params']);
    }

    public function testQueuedDataConversionWritesConvertedValues(): void
    {
        $connection = self::createConnection();
        $connection->insert('akeneo_product_group', ['id' => 1, 'variationAxes' => serialize(['size']), 'associations' => serialize(['UPSELL' => ['SKU-1']])]);
        $connection->insert('akeneo_product_group', ['id' => 2, 'variationAxes' => serialize(['color', 'size']), 'associations' => '[]']);
        $connection->insert('akeneo_api_configuration_product', ['id' => 1, 'attributeMapping' => 'N;']);

        $migration = new Version20261121000000($connection, new NullLogger());
        $migration->up(new Schema());
        foreach (self::statements($migration) as $statement) {
            // SQLite cannot run the MySQL ALTER TABLE statements.
            if (str_starts_with($statement['sql'], 'UPDATE ')) {
                $connection->executeStatement($statement['sql'], $statement['params']);
            }
        }

        self::assertSame(
            [['variationAxes' => '["size"]', 'associations' => '{"UPSELL":["SKU-1"]}'], ['variationAxes' => '["color","size"]', 'associations' => '[]']],
            $connection->fetchAllAssociative('SELECT variationAxes, associations FROM akeneo_product_group ORDER BY id'),
        );
        self::assertNull($connection->fetchOne('SELECT attributeMapping FROM akeneo_api_configuration_product'));
    }

    public static function arrayProvider(): iterable
    {
        yield 'empty' => [[], '[]'];
        yield 'list' => [['en_US', 'fr_FR'], '["en_US","fr_FR"]'];
        yield 'nested map' => [['size' => ['S', 'M'], 'enabled' => true, 'ratio' => 1.0], '{"size":["S","M"],"enabled":true,"ratio":1.0}'];
        yield 'unicode' => [['label' => 'Étagère'], '{"label":"\u00c9tag\u00e8re"}'];
    }

    public static function jsonProvider(): iterable
    {
        foreach (self::arrayProvider() as $name => [, $json]) {
            yield $name => [$json];
        }
    }

    /**
     * SQLite in memory, seen by the migration as MySQL.
     */
    private static function createConnection(): Connection
    {
        $configuration = new Configuration();
        $configuration->setMiddlewares([new class() implements Middleware {
            public function wrap(Driver $driver): Driver
            {
                return new class($driver) extends AbstractDriverMiddleware {
                    public function getDatabasePlatform(mixed ...$arguments): AbstractPlatform
                    {
                        return new MySQLPlatform();
                    }

                    /** DBAL 3 only */
                    public function createDatabasePlatformForVersion($version): AbstractPlatform
                    {
                        return new MySQLPlatform();
                    }
                };
            }
        }]);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true], $configuration);

        foreach (self::TABLES as $table => $columns) {
            $connection->executeStatement(sprintf(
                'CREATE TABLE %s (id INTEGER PRIMARY KEY, %s)',
                $table,
                implode(', ', array_map(static fn (string $column): string => $column . ' TEXT', $columns)),
            ));
        }

        return $connection;
    }

    /**
     * @return list<array{sql: string, params: array}>
     */
    private static function statements(Version20261121000000 $migration): array
    {
        return array_map(
            static fn (Query $query): array => ['sql' => $query->getStatement(), 'params' => $query->getParameters()],
            $migration->getSql(),
        );
    }

    private static function conversionSql(string $table, string $column, int $rowCount): string
    {
        return sprintf(
            'UPDATE `%s` SET `%s` = CASE id %s END WHERE id IN (%s)',
            $table,
            $column,
            implode(' ', array_fill(0, $rowCount, 'WHEN ? THEN ?')),
            implode(', ', array_fill(0, $rowCount, '?')),
        );
    }

    private static function indexOf(array $statements, string $sql, array $params): int
    {
        $index = array_search(['sql' => $sql, 'params' => $params], $statements, true);
        self::assertIsInt($index, sprintf('Statement "%s" with parameters %s is not queued.', $sql, json_encode($params)));

        return $index;
    }

    private static function indexOfAlter(array $statements, string $table): int
    {
        foreach ($statements as $index => $statement) {
            if (str_starts_with($statement['sql'], sprintf('ALTER TABLE `%s` ', $table))) {
                return $index;
            }
        }

        self::fail(sprintf('No ALTER TABLE queued for "%s".', $table));
    }
}
