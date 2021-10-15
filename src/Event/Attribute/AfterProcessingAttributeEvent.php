<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Attribute;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

class AfterProcessingAttributeEvent extends AbstractResourceEvent
{
    private AttributeInterface $attribute;

    public function __construct(array $resource, AttributeInterface $taxon)
    {
        parent::__construct($resource);

        $this->attribute = $taxon;
    }

    public function getAttribute(): AttributeInterface
    {
        return $this->attribute;
    }
}
