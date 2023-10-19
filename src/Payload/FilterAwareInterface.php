<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

interface FilterAwareInterface
{
    public function getCustomFilters(): array;

    public function setCustomFilters(array $customFilters = []): void;
}
