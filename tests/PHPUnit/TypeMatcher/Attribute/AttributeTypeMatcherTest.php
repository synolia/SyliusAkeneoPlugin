<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\TypeMatcher\Attribute;

use Sylius\Component\Attribute\AttributeType\TextAttributeType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeTypeMapping;
use Synolia\SyliusAkeneoPlugin\Exceptions\UnsupportedAttributeTypeException;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\AttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\BooleanAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DatabaseMappingAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\DateAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\IntegerAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MetricAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\MultiSelectAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\SelectAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TextareaAttributeTypeMatcher;
use Synolia\SyliusAkeneoPlugin\TypeMatcher\Attribute\TextAttributeTypeMatcher;

/**
 * @internal
 *
 * @coversNothing
 */
final class AttributeTypeMatcherTest extends KernelTestCase
{
    private const FAKE_AKENEO_ATTRIBUTE_TYPE = 'my_fake_attribute_type';

    private ?AttributeTypeMatcher $attributeTypeMatcher = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->attributeTypeMatcher = $this->getContainer()->get(AttributeTypeMatcher::class);
    }

    public function testDatabaseAttributeTypeMatching(): void
    {
        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->getContainer()->get('doctrine')->getManager();
        $attributeTypeMapping = $manager->getRepository(AttributeTypeMapping::class)->findOneBy([
            'akeneoAttributeType' => self::FAKE_AKENEO_ATTRIBUTE_TYPE,
        ]);

        if (!$attributeTypeMapping instanceof AttributeTypeMapping) {
            $attributeTypeMapping = new AttributeTypeMapping();
            $manager->persist($attributeTypeMapping);
            $attributeTypeMapping
                ->setAttributeType(TextAttributeType::TYPE)
                ->setAkeneoAttributeType('my_fake_attribute_type')
            ;
            $manager->flush();
        }

        $typeMatcher = $this->attributeTypeMatcher->match('my_fake_attribute_type');
        $this->assertInstanceOf(DatabaseMappingAttributeTypeMatcher::class, $typeMatcher);
        $this->assertEquals(TextAttributeType::TYPE, $typeMatcher->getType());
    }

    /** @dataProvider supportedAttributeTypeDataProvider */
    public function testSupportedAttributeTypeMatcher(
        string $akeneoAttributeType,
        string $expectedTypeMatcherClass,
    ): void {
        $this->assertInstanceOf($expectedTypeMatcherClass, $this->attributeTypeMatcher->match($akeneoAttributeType));
    }

    /** @dataProvider unsupportedAttributeTypeDataProvider */
    public function testUnsupportedAttributeTypeMatcher(string $akeneoAttributeType): void
    {
        $this->expectException(UnsupportedAttributeTypeException::class);
        $this->attributeTypeMatcher->match($akeneoAttributeType);
    }

    public function supportedAttributeTypeDataProvider(): \Generator
    {
        yield ['pim_catalog_identifier', TextAttributeTypeMatcher::class];
        yield ['pim_catalog_text', TextAttributeTypeMatcher::class];
        yield ['pim_catalog_textarea', TextareaAttributeTypeMatcher::class];
        yield ['pim_catalog_number', IntegerAttributeTypeMatcher::class];
        yield ['pim_catalog_boolean', BooleanAttributeTypeMatcher::class];
        yield ['pim_catalog_simpleselect', SelectAttributeTypeMatcher::class];
        yield ['pim_catalog_multiselect', MultiSelectAttributeTypeMatcher::class];
        yield ['pim_catalog_date', DateAttributeTypeMatcher::class];
        yield ['pim_catalog_metric', MetricAttributeTypeMatcher::class];
    }

    public function unsupportedAttributeTypeDataProvider(): \Generator
    {
        yield ['pim_catalog_price_collection'];
        yield ['pim_catalog_image'];
        yield ['pim_catalog_file'];
    }
}
