<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

use Synolia\SyliusAkeneoPlugin\Command\Context\CommandContextInterface;
use Synolia\SyliusAkeneoPlugin\Configuration\ConfigurationContextInterface;

interface PayloadInterface extends ConfigurationContextInterface, BatchPayloadInterface
{
    public function getCommandContext(): CommandContextInterface;

    public function hasCommandContext(): bool;
}
