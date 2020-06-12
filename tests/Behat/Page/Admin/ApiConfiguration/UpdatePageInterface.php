<?php

declare(strict_types=1);

namespace Tests\Synolia\SyliusAkeneoPlugin\Behat\Page\Admin\ApiConfiguration;

use Sylius\Behat\Page\Admin\Crud\UpdatePageInterface as BaseUpdatePageInterface;

interface UpdatePageInterface extends BaseUpdatePageInterface
{
    public function setBaseUrl(string $url): void;

    public function containsErrorWithMessage(string $message, bool $strict = true): bool;
}
