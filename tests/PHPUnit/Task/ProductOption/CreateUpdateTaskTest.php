<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ProductOption;

use Akeneo\Pim\ApiClient\Api\LocaleApi;
use donatj\MockWebServer\Response;
use Sylius\Component\Product\Model\ProductOption;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Factory\AttributePipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Attribute\AttributePayload;
use Synolia\SyliusAkeneoPlugin\Provider\TaskProvider;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformer;
use Synolia\SyliusAkeneoPlugin\Transformer\ProductOptionValueDataTransformerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class CreateUpdateTaskTest extends AbstractTaskTestCase
{
    private ?TaskProvider $taskProvider = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProvider = $this->getContainer()->get(TaskProvider::class);

        $this->server->setResponseOfPath(
            '/' . sprintf(LocaleApi::LOCALES_URI),
            new Response($this->getFileContent('locales.json'), [], HttpResponse::HTTP_OK),
        );
    }

    public function testCreateUpdateTask(): void
    {
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
            'color_akeneo-black',
            'color_akeneo-blue',
            'color_akeneo-brown',
            'color_akeneo-green',
            'color_akeneo-grey',
            'color_akeneo-orange',
            'color_akeneo-pink',
            'color_akeneo-red',
            'color_akeneo-white',
            'color_akeneo-yellow',
        ];
        $values = $productOption->getValues();

        /** @var \Sylius\Component\Product\Model\ProductOptionValue $value */
        foreach ($values as $value) {
            $this->assertTrue(
                \in_array(
                    $value->getCode(),
                    $expectedValueCodes,
                    true,
                ),
            );
        }
    }

    private function assertColorisProductOptionValues(ProductOption $productOption): void
    {
        $expectedValueCodes = [
            'coloris_akeneo-black',
            'coloris_akeneo-white',
        ];
        $values = $productOption->getValues();

        /** @var \Sylius\Component\Product\Model\ProductOptionValue $value */
        foreach ($values as $value) {
            $this->assertTrue(
                \in_array(
                    $value->getCode(),
                    $expectedValueCodes,
                    true,
                ),
            );
        }
    }

    private function assertProductOptionValuesTranslations(ProductOption $productOption): void
    {
        /** @var ProductOptionValueDataTransformerInterface $optionValueDataTransformer */
        $optionValueDataTransformer = $this->getContainer()->get(ProductOptionValueDataTransformer::class);

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
