<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Resource\ModelApi;

use Akeneo\Pim\ApiClient\Api\Operation\ListableResourceInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

class ModelApiProvider implements ModelApiProviderInterface
{
    /**
     * @param ModelApiInterface[] $modelApis
     */
    public function __construct(
        #[TaggedIterator(ModelApiInterface::class)]
        private iterable $modelApis = [],
    ) {
    }

    public function get(PayloadInterface $payload): ListableResourceInterface
    {
        foreach ($this->modelApis as $modelApi) {
            if ($modelApi->support($payload)) {
                return $modelApi;
            }
        }

        throw new \LogicException('Could not find Akeneo Model Api for payload');
    }
}
