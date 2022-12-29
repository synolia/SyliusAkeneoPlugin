<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\Attribute;

use Akeneo\Pim\ApiClient\Api\AttributeApi;
use donatj\MockWebServer\Response;
use Sylius\Component\Product\Model\ProductAttribute;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Task\Attribute\ProcessAttributeTask;
use Synolia\SyliusAkeneoPlugin\Task\SetupTask;
use Synolia\SyliusAkeneoPlugin\Task\TearDownTask;

/**
 * @internal
 *
 * @coversNothing
 */
final class ProcessAttributesTaskTest extends AbstractTaskTest
{
    public function testNoAttributes(): void
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('empty_attributes.json'), [], HttpResponse::HTTP_OK),
        );

        $attributesCount = $this->getContainer()->get('sylius.repository.product_attribute')->count([]);
        $payload = new AttributePayload($this->createClient());

        $setupAttributeTask = $this->taskProvider->get(SetupTask::class);
        $setupPayload = $setupAttributeTask->__invoke($payload);

        /** @var ProcessAttributeTask $task */
        $task = $this->taskProvider->get(ProcessAttributeTask::class);
        $task->__invoke($setupPayload);

        $finalAttributeCount = $this->getContainer()->get('sylius.repository.product_attribute')->count([]);

        $this->assertEquals($attributesCount, $finalAttributeCount);
    }

    public function testCreateUpdateTask(): void
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(AttributeApi::ATTRIBUTES_URI),
            new Response($this->getFileContent('attributes_all.json'), [], HttpResponse::HTTP_OK),
        );

        $initialPayload = new AttributePayload($this->createClient());
        $initialPayload->disableBatching();
        $setupAttributeTask = $this->taskProvider->get(SetupTask::class);
        $setupPayload = $setupAttributeTask->__invoke($initialPayload);

        /** @var ProcessAttributeTask $task */
        $task = $this->taskProvider->get(ProcessAttributeTask::class);
        $payload = $task->__invoke($setupPayload);

        $tearDownAttributeTask = $this->taskProvider->get(TearDownTask::class);
        $tearDownAttributeTask->__invoke($payload);

        /** @var \Sylius\Component\Product\Model\ProductAttribute $careInstructionProductAttribute */
        $careInstructionProductAttribute = $this->getContainer()->get('sylius.repository.product_attribute')->findOneBy(['code' => 'care_instructions']);
        $this->assertNotNull($careInstructionProductAttribute);
        $this->assertEquals('Instructions d\'entretien', $careInstructionProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Care instructions', $careInstructionProductAttribute->getTranslation('en_US')->getName());

        /** @var \Sylius\Component\Product\Model\ProductAttribute $colorProductAttribute */
        $colorProductAttribute = $this->getContainer()->get('sylius.repository.product_attribute')->findOneBy(['code' => 'color']);
        $this->assertNotNull($colorProductAttribute);
        $this->assertEquals('Couleur', $colorProductAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $colorProductAttribute->getTranslation('en_US')->getName());
    }

    public function testCreateAttributeOptions(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        $initialPayload->setProcessAsSoonAsPossible(false);

        $importAttributePipeline = $this->getContainer()->get(AttributePipelineFactory::class)->create();
        $importAttributePipeline->process($initialPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository $attributeRepository */
        $attributeRepository = $this->getContainer()->get('sylius.repository.product_attribute');

        /** @var ProductAttribute $productAttribute */
        $productAttribute = $attributeRepository->findOneBy(['code' => 'color']);
        $this->assertNotNull($productAttribute);

        $this->assertProductAttributeTranslations($productAttribute);
        $this->assertProductAttributeChoices($productAttribute);
        $this->assertProductAttributeChoicesTranslations($productAttribute);

        /** @var ProductAttribute $productAttribute */
        $productAttribute = $attributeRepository->findOneBy(['code' => 'coloris']);
        $this->assertNotNull($productAttribute);
        $this->assertEquals('Coloris', $productAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('[coloris]', $productAttribute->getTranslation('en_US')->getName());
    }

    private function assertProductAttributeTranslations(ProductAttribute $productAttribute): void
    {
        $this->assertEquals('Couleur', $productAttribute->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $productAttribute->getTranslation('en_US')->getName());
    }

    public function testGetOptions(): void
    {
        $initialPayload = new AttributePayload($this->createClient());
        $initialPayload->setProcessAsSoonAsPossible(false);

        $importAttributePipeline = $this->getContainer()->get(AttributePipelineFactory::class)->create();
        $importAttributePipeline->process($initialPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository $attributeRepository */
        $attributeRepository = $this->getContainer()->get('sylius.repository.product_attribute');

        /** @var ProductAttribute $productAttribute */
        $productAttribute = $attributeRepository->findOneBy(['code' => 'color']);
        $this->assertNotNull($productAttribute);

        $this->assertProductAttributeTranslations($productAttribute);
        $this->assertProductAttributeChoices($productAttribute);
        $this->assertProductAttributeChoicesTranslations($productAttribute);

        /** @var ProductAttribute $productAttribute */
        $productAttribute = $attributeRepository->findOneBy(['code' => 'coloris']);
        $this->assertNotNull($productAttribute);
        //Reference Entity attribute have not choices
        $this->assertArrayNotHasKey('choices', $productAttribute->getConfiguration());

        /** @var \Synolia\SyliusAkeneoPlugin\Repository\ProductOptionRepository $productOptionRepository */
        $productOptionRepository = $this->getContainer()->get('sylius.repository.product_option');
        $colorisOption = $productOptionRepository->findOneBy(['code' => 'coloris']);
        $this->assertNotNull($colorisOption);
        $this->assertEquals('Coloris', $colorisOption->getTranslation('fr_FR')->getName());
        $this->assertEquals('[coloris]', $colorisOption->getTranslation('en_US')->getName());
        $this->assertEquals(2, $colorisOption->getValues()->count());
        $this->assertEquals('coloris_akeneo-black', $colorisOption->getValues()->first()->getCode());
        $this->assertEquals('coloris_akeneo-white', $colorisOption->getValues()->last()->getCode());
    }

    private function assertProductAttributeChoices(ProductAttribute $productAttribute): void
    {
        $expectedChoiceCodes = [
            'akeneo-black',
            'akeneo-blue',
            'akeneo-brown',
            'akeneo-green',
            'akeneo-grey',
            'akeneo-orange',
            'akeneo-pink',
            'akeneo-red',
            'akeneo-white',
            'akeneo-yellow',
        ];
        /** @var array $choices */
        $choices = $productAttribute->getConfiguration()['choices'];

        foreach (array_keys($choices) as $attributeOptionCode) {
            $this->assertTrue(
                \in_array(
                    $attributeOptionCode,
                    $expectedChoiceCodes,
                    true,
                ),
            );
        }
    }

    private function assertProductAttributeChoicesTranslations(ProductAttribute $productAttribute): void
    {
        $blackChoice = $productAttribute->getConfiguration()['choices']['akeneo-black'];

        $this->assertEquals('Noir', $blackChoice['fr_FR']);
        $this->assertEquals('Black', $blackChoice['en_US']);
    }
}
