<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Retriever;

interface EditionRetrieverInterface
{
    public function getEdition(): string;
}
