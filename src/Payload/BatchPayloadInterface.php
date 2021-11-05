<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Payload;

interface BatchPayloadInterface
{
    public function getIds(): array;

    public function setIds(array $ids): self;

    public function getTmpTableName(): string;

    public function setTmpTableName(string $name): self;

    public function getCommandName(): string;

    public function setCommandName(string $name): self;
}
