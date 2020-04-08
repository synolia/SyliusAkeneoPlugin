<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Logger;

final class Messages
{
    public static function commandAlreadyRunning(): string
    {
        return 'The command is already running in another process.';
    }

    public static function endOfCommand(string $command): string
    {
        return \sprintf('End of : %s', $command);
    }

    public static function retrieveFromAPI(string $type): string
    {
        return \sprintf('Retrieve %s from Akeneo API', $type);
    }

    public static function totalToImport(string $type, int $value): string
    {
        return \sprintf('Total %s to import %d', $type, $value);
    }

    public static function noCodeToImport(string $type, int $value): string
    {
        return \sprintf('%d %s do not have a code and will not be imported.', $value, $type);
    }

    public static function removalNoLongerExist(string $type): string
    {
        return \sprintf('Removal of %s which no longer exists.', $type);
    }

    public static function countOfDeleted(string $type, int $value): string
    {
        return \sprintf('%s : %d Delete', $type, $value);
    }

    public static function countCreateAndUpdate(string $type, int $create, int $update): string
    {
        return \sprintf('%s : %d Create, %d Update', $type, $create, $update);
    }

    public static function hasBeenDeleted(string $type, string $code): string
    {
        return sprintf('%s "%s" will be deleted', $type, $code);
    }

    public static function createOrUpdate(string $type): string
    {
        return \sprintf('Create or update %s.', $type);
    }

    public static function hasBeenCreated(string $type, string $code): string
    {
        return sprintf('%s "%s" will be created', $type, $code);
    }

    public static function hasBeenUpdated(string $type, string $code): string
    {
        return sprintf('%s "%s" will be updated', $type, $code);
    }
}
