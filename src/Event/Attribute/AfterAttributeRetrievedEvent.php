<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Event\Attribute;

use Sylius\Component\Attribute\Model\AttributeInterface;
use Synolia\SyliusAkeneoPlugin\Event\AbstractResourceEvent;

final class AfterAttributeRetrievedEvent extends AbstractResourceEvent
{
    public function __construct(array $resource, private AttributeInterface $attribute)
    {
        parent::__construct($resource);
    }

    public function getAttribute(): AttributeInterface
    {
        return $this->attribute;
    }
}
