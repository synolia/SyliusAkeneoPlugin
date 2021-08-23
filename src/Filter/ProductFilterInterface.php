<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Filter;

interface ProductFilterInterface
{
    public function getModelQueryParameters(): array;

    public function getQueryParameters(): array;

    public function getChannel(): ?string;
}
