<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SynoliaSyliusAkeneoPlugin extends Bundle implements \Stringable
{
    use SyliusPluginTrait;

    public function __toString(): string
    {
        return 'SynoliaSyliusAkeneoPlugin';
    }

    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
