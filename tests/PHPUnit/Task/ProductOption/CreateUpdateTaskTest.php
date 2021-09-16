<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductOption;

use Akeneo\Pim\ApiClient\Api\LocaleApi;
use donatj\MockWebServer\Response;
use Sylius\Component\Product\Model\ProductOption;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Entity\ApiConfiguration;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\AbstractAttributeOptionTask;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;

/**
 * @internal
 * @coversNothing
 */
final class CreateUpdateTaskTest extends AbstractTaskTest
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoTaskProvider */
    private $taskProvider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = $this->getContainer()->get(AkeneoTaskProvider::class);

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK)
        );
    }

    public function testCreateUpdateTask(): void
    {
        $this->createConfiguration();
        $attributesPayload = new AttributePayload($this->createClient());

        $importAttributePipeline = $this->getContainer()->get(AttributePipelineFactory::class)->create();
        $importAttributePipeline->process($attributesPayload);

        $productOptionRepository = $this->getContainer()->get('sylius.repository.product_option');
        /** @var \Sylius\Component\Product\Model\ProductOptionInterface $colorProductOption */
        $colorProductOption = $productOptionRepository->findOneBy(['code' => 'color']);
        /** @var \Sylius\Component\Product\Model\ProductOptionInterface $colorProductOption */
        $colorisProductOption = $productOptionRepository->findOneBy(['code' => 'coloris']);

        $this->assertNotNull($colorProductOption);
        $this->assertNotNull($colorisProductOption);

        $this->assertColorProductOptionTranslations($colorProductOption);
        $this->assertColorisProductOptionTranslations($colorisProductOption);

        $this->assertProductOptionValuesTranslations($colorProductOption);
        $this->assertColorProductOptionValues($colorProductOption);
        $this->assertColorisProductOptionValues($colorisProductOption);
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

    private function assertColorProductOptionTranslations(ProductOption $productOption): void
    {
        $this->manager->refresh($productOption);
        $this->assertEquals('Couleur', $productOption->getTranslation('fr_FR')->getName());
        $this->assertEquals('Color', $productOption->getTranslation('en_US')->getName());
    }

    private function assertColorisProductOptionTranslations(ProductOption $productOption): void
    {
        $this->manager->refresh($productOption);
        $this->assertEquals('Coloris', $productOption->getTranslation('fr_FR')->getName());
        $this->assertEquals('[coloris]', $productOption->getTranslation('en_US')->getName());
    }

    private function assertColorProductOptionValues(ProductOption $productOption): void
    {
        $expectedValueCodes = [
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'black',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'blue',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'brown',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'green',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'grey',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'orange',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'pink',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'red',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'white',
            'color_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'yellow',
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

    private function assertColorisProductOptionValues(ProductOption $productOption): void
    {
        $expectedValueCodes = [
            'coloris_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'black',
            'coloris_' . AbstractAttributeOptionTask::AKENEO_PREFIX . 'white',
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
        /** @var ProductOptionValueDataTransformerInterface $optionValueDataTransformer */
        $optionValueDataTransformer = $this->getContainer()->get(ProductOptionValueDataTransformerInterface::class);

        /** @var \Sylius\Component\Resource\Repository\RepositoryInterface $productOptionValueRepository */
        $productOptionValueRepository = $this->getContainer()->get('sylius.repository.product_option_value');
        /** @var \Sylius\Component\Resource\Repository\RepositoryInterface $productOptionValueTranslationRepository */
        $productOptionValueTranslationRepository = $this->getContainer()->get('sylius.repository.product_option_value_translation');

        /** @var \Sylius\Component\Product\Model\ProductOptionValue $productOptionValue */
        $productOptionValue = $productOptionValueRepository->findOneBy([
            'code' => $optionValueDataTransformer->transform($productOption, 'black'),
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
