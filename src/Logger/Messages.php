<?php

declare(strict_types=1);

namespace Synolia\SyliusAkeneoPlugin\Logger;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
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

    public static function countCreateAndExist(string $type, int $create, int $exist): string
    {
        return \sprintf('%s : %d Create, %d Already exist', $type, $create, $exist);
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

    public static function countItems(string $type, int $itemCount): string
    {
        return sprintf('%s has now %d elements', $type, $itemCount);
    }

    public static function hasBeenAlreadyExist(string $type, string $code): string
    {
        return sprintf('%s "%s" already exist', $type, $code);
    }

    public static function setVariationAxeToFamily(string $type, string $entity, string $axe): string
    {
        return sprintf('%s "%s" has variant axe "%s"', $type, $entity, $axe);
    }

    public static function noConfigurationSet(string $config, string $for): string
    {
        return sprintf('You must configure %s to %s', $config, $for);
    }

    public static function totalExcludedFromImport(string $type, int $value): string
    {
        return \sprintf('Total %s excluded from import %d', $type, $value);
    }
}
