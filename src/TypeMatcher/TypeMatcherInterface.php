<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\TypeMatcher;

interface TypeMatcherInterface
{
    public function support(string $akeneoType): bool;

    public function getType(): string;

    public function getBuilder(): string;
}
