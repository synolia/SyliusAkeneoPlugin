<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductOption;

use Sylius\Component\Product\Model\ProductOption;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Manager\ProductOptionManager;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask;
use Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask;

final class CreateUpdateTaskTest extends AbstractTaskTest
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
        $this->createConfiguration();
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = self::$container->get(AttributePipelineFactory::class)->create();
        $attributesPayload = $importAttributePipeline->process($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\RetrieveOptionsTask $retrieveOptionsTask */
        $retrieveOptionsTask = $this->taskProvider->get(RetrieveOptionsTask::class);
        $optionsPayload = $retrieveOptionsTask->__invoke($attributesPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\AttributeOption\CreateUpdateDeleteTask $createUpdateDeleteAttributeOptionTask */
        $createUpdateDeleteAttributeOptionTask = $this->taskProvider->get(CreateUpdateDeleteTask::class);
        $attributeOptionPayload = $createUpdateDeleteAttributeOptionTask->__invoke($optionsPayload);

        /** @var \Synolia\SyliusAkeneoPlugin\Task\Option\CreateUpdateTask $createUpdateOptionTask */
        $createUpdateOptionTask = $this->taskProvider->get(CreateUpdateTask::class);
        $createUpdateOptionTask->__invoke($attributeOptionPayload);

        $productOptionRepository = self::$container->get('sylius.repository.product_option');
        /** @var \Sylius\Component\Product\Model\ProductOptionInterface $productOption */
        $productOption = $productOptionRepository->findOneBy(['code' => 'color']);

        $this->assertNotNull($productOption);
        $this->assertProductOptionTranslations($productOption);
        $this->assertProductOptionValues($productOption);
        $this->assertProductOptionValuesTranslations($productOption);
    }

    private function createConfiguration(): void
    {
        $apiConfiguration = new ApiConfiguration();
        $apiConfiguration->setBaseUrl('');
        $apiConfiguration->setApiClientId('');
        $apiConfiguration->setApiClientSecret('');
        $apiConfiguration->setPaginationSize(100);
        $apiConfiguration->setIsEnterprise(true);
        $apiConfiguration->setUsername('');
        $apiConfiguration->setPassword('');
        $this->manager->persist($apiConfiguration);
        $this->manager->flush();
    }

    private function assertProductOptionTranslations(ProductOption $productOption): void
    {
        $this->manager->refresh($productOption);
        $this->assertEquals('Couleur', $productOption->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $productOption->getTranslation('en_US')->getName());
    }

    private function assertProductOptionValues(ProductOption $productOption): void
    {
        $expectedValueCodes = [
            'color_black',
            'color_blue',
            'color_brown',
            'color_green',
            'color_grey',
            'color_orange',
            'color_pink',
            'color_red',
            'color_white',
            'color_yellow',
        ];
        $values = $productOption->getValues();

        /** @var \Sylius\Component\Product\Model\ProductOptionValue $value */
        foreach ($values as $value) {
            $this->assertEquals(
                true,
                \in_array(
                    $value->getCode(),
                    $expectedValueCodes,
                    true
                )
            );
        }
    }

    private function assertProductOptionValuesTranslations(ProductOption $productOption): void
    {
        /** @var \Sylius\Component\Resource\Repository\RepositoryInterface $productOptionValueRepository */
        $productOptionValueRepository = self::$container->get('sylius.repository.product_option_value');
        /** @var \Sylius\Component\Resource\Repository\RepositoryInterface $productOptionValueTranslationRepository */
        $productOptionValueTranslationRepository = self::$container->get('sylius.repository.product_option_value_translation');

        /** @var \Sylius\Component\Product\Model\ProductOptionValue $productOptionValue */
        $productOptionValue = $productOptionValueRepository->findOneBy([
            'code' => ProductOptionManager::getOptionValueCodeFromProductOption($productOption, 'black'),
            'option' => $productOption,
        ]);

        $expectedTranslations = ['fr_FR' => 'Noir', 'en_US' => 'Black'];

        foreach ($expectedTranslations as $locale => $translationValue) {
            $translation = $productOptionValueTranslationRepository->findOneBy([
                'translatable' => $productOptionValue,
                'locale' => $locale,
            ]);
            $this->assertEquals($translationValue, $translation->getValue());
        }
    }
}
