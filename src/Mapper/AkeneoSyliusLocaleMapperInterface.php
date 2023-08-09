<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Mapper;

interface AkeneoSyliusLocaleMapperInterface
{
    public function map(string $akeneoLocale): array;

    public function unmap(string $syliusLocale): string;
}
