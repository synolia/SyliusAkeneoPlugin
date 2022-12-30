<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Retriever;

use Akeneo\Pim\ApiClient\Api\FamilyApi;
use Akeneo\Pim\ApiClient\Api\FamilyVariantApi;
use donatj\MockWebServer\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetriever;
use Synolia\SyliusAkeneoPlugin\Retriever\FamilyRetrieverInterface;
use Tests\Synolia\SyliusAkeneoPlugin\PHPUnit\Api\ApiTestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FamilyRetrieverTest extends ApiTestCase
{
    private ?FamilyRetrieverInterface $familyRetriever = null;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $this->manager = $this->getContainer()->get('doctrine')->getManager();
        $this->familyRetriever = $this->getContainer()->get(FamilyRetriever::class);

        $this->server->setResponseOfPath(
            '/' . FamilyApi::FAMILIES_URI,
            new Response($this->getFileContent('families.json'), [], HttpResponse::HTTP_OK),
        );
        $this->server->setResponseOfPath(
            '/' . sprintf(FamilyVariantApi::FAMILY_VARIANTS_URI, 'clothing'),
            new Response($this->getFileContent('family_clothing_variants.json'), [], HttpResponse::HTTP_OK),
        );

        $this->manager->flush();
    }

    protected function tearDown(): void
    {
        $this->manager->close();
        $this->manager = null;

        $this->server->stop();

        parent::tearDown();
    }

    public function testGetFamilyCode(): void
    {
        $familyCode = $this->familyRetriever->getFamilyCodeByVariantCode('clothing_color');
        $this->assertSame('clothing', $familyCode);
    }

    public function testGetFamilyCodeWithWrongVariantCodeThrowAnException(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unable to find family for variant "wrong_variant_code"');

        $this->familyRetriever->getFamilyCodeByVariantCode('wrong_variant_code');
    }
}
