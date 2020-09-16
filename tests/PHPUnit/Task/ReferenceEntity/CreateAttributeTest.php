<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Task\ReferenceEntity;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityAttributeOptionApi;
use Akeneo\PimEnterprise\ApiClient\Api\ReferenceEntityRecordApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityAttributeType;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntityImageSubAttributeType;
use Synolia\SyliusAkeneoPlugin\Component\Attribute\AttributeType\ReferenceEntitySelectSubAttributeType;
use Synolia\SyliusAkeneoPlugin\Factory\ReferenceEntityPipelineFactory;
use Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload;
use Synolia\SyliusAkeneoPlugin\Repository\ProductAttributeRepository;
use Synolia\SyliusAkeneoPlugin\Task\AttributeOption\AbstractAttributeOptionTask;

class CreateAttributeTest extends AbstractTaskTest
{
    private const REFERENCE_ENTITY_CODE = 'test_entite_couleur';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testImportReferenceEntity(): void
    {
        $this->importAll();

        /** @var \App\Entity\Product\ProductAttribute $referenceEntityAttribute */
        $referenceEntityAttribute = self::$container->get(ProductAttributeRepository::class)->findOneBy(['code' => self::REFERENCE_ENTITY_CODE]);

        $this->assertNotNull($referenceEntityAttribute);
        $this->assertSame(ReferenceEntityAttributeType::TYPE, $referenceEntityAttribute->getType());

        //get expected choices from json file
        $expectedChoicesArray = \json_decode($this->getFileContent('entity_couleur_records.json'), true)['_embedded']['items'];
        $expectedChoices = [];
        foreach ($expectedChoicesArray as $value) {
            $expectedChoices[] = AbstractAttributeOptionTask::AKENEO_PREFIX . $value['code'];
        }

        //Check configurations keys
        $this->assertSame($expectedChoices, \array_keys($referenceEntityAttribute->getConfiguration()['choices']));

        //Check configuration key translations
        $this->assertSame('BEIGE', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-beige']['fr_FR']);
        $this->assertSame(' ', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-beige']['en_US']);
        $this->assertSame('BLANC', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-blanc']['fr_FR']);
        $this->assertSame(' ', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-blanc']['en_US']);
        $this->assertSame('BLEU', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-bleu']['fr_FR']);
        $this->assertSame(' ', $referenceEntityAttribute->getConfiguration()['choices']['akeneo-bleu']['en_US']);

        //Reference entity select is never multiple
        $this->assertFalse($referenceEntityAttribute->getConfiguration()['multiple']);
    }

    /**
     * @dataProvider subAttributeDataProvider
     */
    public function testImportReferenceEntitySubAttributes(string $subAttributeCode, string $syliusAttributeType): void
    {
        $this->importAll();

        /** @var \App\Entity\Product\ProductAttribute $referenceEntitySubAttribute */
        $referenceEntitySubAttribute = self::$container->get(ProductAttributeRepository::class)->findOneBy([
            'code' => $subAttributeCode,
        ]);

        $this->assertNotNull($referenceEntitySubAttribute);
        $this->assertSame($syliusAttributeType, $referenceEntitySubAttribute->getType());
    }

    public function testImportReferenceEntitySingleOptionSubAttributeOptions(): void
    {
        $this->importAll();

        /** @var \App\Entity\Product\ProductAttribute $referenceEntitySubAttribute */
        $referenceEntitySubAttribute = self::$container->get(ProductAttributeRepository::class)->findOneBy([
            'code' => self::REFERENCE_ENTITY_CODE . '_filtre_couleur_1',
        ]);

        //get expected choices from json file
        $expectedChoicesArray = \json_decode($this->getFileContent('entity_couleur_filtre_couleur_1_options.json'), true);
        $expectedChoices = [];
        foreach ($expectedChoicesArray as $value) {
            $expectedChoices[] = AbstractAttributeOptionTask::AKENEO_PREFIX . $value['code'];
        }

        //Check configurations keys
        $this->assertSame($expectedChoices, \array_keys($referenceEntitySubAttribute->getConfiguration()['choices']));

        //Check configuration key translations
        $this->assertSame('Bleu', $referenceEntitySubAttribute->getConfiguration()['choices']['akeneo-bleu']['fr_FR']);
        $this->assertSame('Blue', $referenceEntitySubAttribute->getConfiguration()['choices']['akeneo-bleu']['en_US']);
        $this->assertSame('Noir', $referenceEntitySubAttribute->getConfiguration()['choices']['akeneo-noir']['fr_FR']);
        $this->assertSame('Black', $referenceEntitySubAttribute->getConfiguration()['choices']['akeneo-noir']['en_US']);

        $this->assertFalse($referenceEntitySubAttribute->getConfiguration()['multiple']);
    }

    public function subAttributeDataProvider(): \Generator
    {
        yield [self::REFERENCE_ENTITY_CODE . '_filtre_couleur_1', ReferenceEntitySelectSubAttributeType::TYPE];
        yield [self::REFERENCE_ENTITY_CODE . '_image', ReferenceEntityImageSubAttributeType::TYPE];
    }

    private function importAll()
    {
        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityRecordApi::REFERENCE_ENTITY_RECORDS_URI, 'couleur'),
            new Response($this->getFileContent('entity_couleur_records.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityAttributeApi::REFERENCE_ENTITY_ATTRIBUTES_URI, 'couleur'),
            new Response($this->getFileContent('entity_couleur_attributes.json'), [], HttpResponse::HTTP_OK)
        );

        $this->server->setResponseOfPath(
            '/' . sprintf(ReferenceEntityAttributeOptionApi::REFERENCE_ENTITY_ATTRIBUTE_OPTIONS_URI, 'couleur', 'filtre_couleur_1'),
            new Response($this->getFileContent('entity_couleur_filtre_couleur_1_options.json'), [], HttpResponse::HTTP_OK)
        );

        $factory = self::$container->get(ReferenceEntityPipelineFactory::class);

        /** @var \League\Pipeline\Pipeline $referenceEntityPipeline */
        $referenceEntityPipeline = $factory->create();

        /** @var \Synolia\SyliusAkeneoPlugin\Payload\ReferenceEntity\ReferenceEntityOptionsPayload $referenceEntityPayload */
        $referenceEntityPayload = new ReferenceEntityOptionsPayload(self::$container->get(AkeneoPimEnterpriseClientInterface::class));
        $referenceEntityPipeline->process($referenceEntityPayload);
    }
}
