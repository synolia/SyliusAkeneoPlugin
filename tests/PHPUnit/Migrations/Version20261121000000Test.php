<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Migrations;

use PHPUnit\Framework\TestCase;
use Synolia\SyliusAkeneoPlugin\Migrations\Version20261121000000;

/**
 * @internal
 *
 * @coversNothing
 */
final class Version20261121000000Test extends TestCase
{
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

    public static function arrayProvider(): iterable
    {
        yield 'empty' => [[], '[]'];
        yield 'list' => [['en_US', 'fr_FR'], '["en_US","fr_FR"]'];
        yield 'nested map' => [['size' => ['S', 'M'], 'enabled' => true, 'ratio' => 1.0], '{"size":["S","M"],"enabled":true,"ratio":1.0}'];
        yield 'unicode' => [['label' => 'Étagère'], '{"label":"\u00c9tag\u00e8re"}'];
    }
}
