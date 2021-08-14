<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Processor\Product;

use Sylius\Component\Core\Model\ProductInterface;
use Synolia\SyliusAkeneoPlugin\Client\ClientFactory;
use Synolia\SyliusAkeneoPlugin\Payload\Product\ProductResourcePayload;
use Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProvider;
use Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface;
use Synolia\SyliusAkeneoPlugin\Task\Product\AddAttributesToProductTask;

class AttributesProcessor implements AttributesProcessorInterface
{
    /** @var \Synolia\SyliusAkeneoPlugin\Provider\AkeneoFamilyPropertiesProvider */
    private $akeneoFamilyPropertiesProvider;

    /** @var \Synolia\SyliusAkeneoPlugin\Task\Product\AddAttributesToProductTask */
    private $addAttributesToProductTask;

    /** @var \Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface */
    private $client;

    /** @var \Synolia\SyliusAkeneoPlugin\Provider\ProductFilterRulesProviderInterface */
    private $productFilterRulesProvider;

    public function __construct(
        AkeneoFamilyPropertiesProvider $akeneoFamilyPropertiesProvider,
        AddAttributesToProductTask $addAttributesToProductTask,
        ClientFactory $clientFactory,
        ProductFilterRulesProviderInterface $productFilterRulesProvider
    ) {
        $this->akeneoFamilyPropertiesProvider = $akeneoFamilyPropertiesProvider;
        $this->addAttributesToProductTask = $addAttributesToProductTask;
        $this->productFilterRulesProvider = $productFilterRulesProvider;
        $this->client = $clientFactory->createFromApiCredentials();
    }

    public function process(ProductInterface $product, array $resource): void
    {
        $filters = $this->productFilterRulesProvider->getProductFiltersRules();
        $family = $this->akeneoFamilyPropertiesProvider->getProperties($resource['family']);

        $productResourcePayload = new ProductResourcePayload($this->client);
        $productResourcePayload
            ->setProduct($product)
            ->setResource($resource)
            ->setFamily($family)
            ->setScope($filters->getChannel())
        ;

        $this->addAttributesToProductTask->__invoke($productResourcePayload);

        unset($family, $productResourcePayload);
    }
}
