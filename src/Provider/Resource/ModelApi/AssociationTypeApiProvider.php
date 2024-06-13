<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Synolia\SyliusAkeneoPlugin\Payload\Association\AssociationTypePayload;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class AssociationTypeApiProvider implements ModelApiInterface
{
    public function support(PayloadInterface $payload): bool
    {
        return $payload instanceof AssociationTypePayload;
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        return $payload->getAkeneoPimClient()->getAssociationTypeApi();
    }
}
