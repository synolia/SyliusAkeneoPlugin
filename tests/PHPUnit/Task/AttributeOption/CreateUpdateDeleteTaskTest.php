<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\AttributeOption;

use Sylius\Component\Product\Model\ProductAttribute;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;

final class CreateUpdateDeleteTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = self::$container->get(AkeneoTaskProvider::class);
    }

    public function testCreateUpdateTask(): void
    {
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask $retrieveOptionsTask */
        $retrieveOptionsTask = $this->taskProvider->get(RetrieveOptionsTask::class);
        $optionsPayload = $retrieveOptionsTask->__invoke($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask $createUpdateDeleteTask */
        $createUpdateDeleteTask = $this->taskProvider->get(CreateUpdateDeleteTask::class);
        $createUpdateDeleteTask->__invoke($optionsPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository $attributeRepository */
        $attributeRepository = self::$container->get('sylius.repository.product_attribute');
        /** @var ProductAttribute $productAttribute */
        $productAttribute = $attributeRepository->findOneBy(['code' => 'color']);
        $this->assertNotNull($productAttribute);

        $this->assertProductAttributeTranslations($productAttribute);
        $this->assertProductAttributeChoices($productAttribute);
        $this->assertProductAttributeChoicesTranslations($productAttribute);
    }

    private function assertProductAttributeTranslations(ProductAttribute $productAttribute): void
    {
        $this->assertEquals('Couleur', $productAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $productAttribute->getTranslation('en_US')->getName());
    }

    private function assertProductAttributeChoices(ProductAttribute $productAttribute): void
    {
        $expectedChoiceCodes = [
            'black',
            'blue',
            'brown',
            'green',
            'grey',
            'orange',
            'pink',
            'red',
            'white',
            'yellow',
        ];
        /** @var array $choices */
        $choices = $productAttribute->getConfiguration()['choices'];

        foreach (array_keys($choices) as $attributeOptionCode) {
            $this->assertEquals(
                true,
                \in_array(
                    $attributeOptionCode,
                    $expectedChoiceCodes,
                    true
                )
            );
        }
    }

    private function assertProductAttributeChoicesTranslations(ProductAttribute $productAttribute): void
    {
        $blackChoice = $productAttribute->getConfiguration()['choices']['black'];

        $this->assertEquals('Noir', $blackChoice['fr_FR']);
        $this->assertEquals('Black', $blackChoice['en_US']);
    }
}
