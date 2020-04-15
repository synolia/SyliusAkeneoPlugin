<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Transformer;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Synolia\SyliusAkeneoPlugin\Entity\AttributeAkeneoSyliusMapping;
use Synolia\SyliusAkeneoPlugin\Transformer\AkeneoAttributeToSyliusAttributeTransformer;

final class AkeneoAttributeToSyliusAttributeTransformerTest extends KernelTestCase
{
    /** @var AkeneoAttributeToSyliusAttributeTransformer */
    private $akeneoAttributeToSyliusAttributeTransformer;

    /** @var ObjectManager */
    private $manager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = self::$container->get('doctrine')->getManager();
        $this->manager->beginTransaction();
        $attributeMapping = new AttributeAkeneoSyliusMapping();
        $attributeMapping
            ->setAkeneoAttribute('name')
            ->setSyliusAttribute('test_succeeded')
        ;

        $this->manager->persist($attributeMapping);
        $this->manager->flush();

        $this->akeneoAttributeToSyliusAttributeTransformer = self::$container->get(AkeneoAttributeToSyliusAttributeTransformer::class);
        self::assertInstanceOf(AkeneoAttributeToSyliusAttributeTransformer::class, $this->akeneoAttributeToSyliusAttributeTransformer);
    }

    protected function tearDown(): void
    {
        $this->manager->rollback();
        $this->manager->close();
        $this->manager = null;

        parent::tearDown();
    }

    public function testTransform(): void
    {
        $attribute = $this->akeneoAttributeToSyliusAttributeTransformer->transform('name');
        $this->assertEquals('test_succeeded', $attribute);
    }
}
