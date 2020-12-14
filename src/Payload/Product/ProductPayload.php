<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload\Product;

use Akeneo\Pim\ApiClient\Pagination\Page;
use Akeneo\Pim\ApiClient\Pagination\ResourceCursorInterface;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;
use Synolia\SyliusAkeneoPlugin\Payload\AbstractPayload;

final class ProductPayload extends AbstractPayload
{
    public const TEMP_AKENEO_TABLE_NAME = 'tmp_akeneo_products';

    public const SELECT_PAGINATION_SIZE = 100;

    /** @var Page|ResourceCursorInterface|null */
    private $resources;

    private ProductItemPayload $simpleProductPayload;

    private ProductItemPayload $configurableProductPayload;

    public function __construct(AkeneoPimEnterpriseClientInterface $akeneoPimClient)
    {
        parent::__construct($akeneoPimClient);

        $this->simpleProductPayload = new ProductItemPayload($akeneoPimClient);
        $this->configurableProductPayload = new ProductItemPayload($akeneoPimClient);
    }

    /**
     * @return Page|ResourceCursorInterface|null
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @param mixed $resources
     */
    public function setResources($resources): void
    {
        $this->resources = $resources;
    }

    public function getSimpleProductPayload(): ProductItemPayload
    {
        return $this->simpleProductPayload;
    }

    public function getConfigurableProductPayload(): ProductItemPayload
    {
        return $this->configurableProductPayload;
    }
}
