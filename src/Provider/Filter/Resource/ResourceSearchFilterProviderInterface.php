<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Provider\Filter\Resource;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Synolia\SyliusAkeneoPlugin\Payload\PayloadInterface;

#[AutoconfigureTag()]
interface ResourceSearchFilterProviderInterface
{
    public function support(PayloadInterface $payload): bool;

    public function get(PayloadInterface $payload): array;
}
