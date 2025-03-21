<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SettingsService
{
    public const EXTENSION_KEY = 'critical_css';

    protected static function getExtensionConfiguration(string $key = ''): mixed
    {
        try {
            return GeneralUtility::makeInstance(ExtensionConfiguration::class)?->get(self::EXTENSION_KEY, $key);
        } catch (ExtensionConfigurationExtensionNotConfiguredException | ExtensionConfigurationPathDoesNotExistException $e) {
            LogService::systemError($e->getMessage() . $e->getCode());
        }

        return null;
    }

    public static function getExtensionKey(): string
    {
        return self::EXTENSION_KEY;
    }

    public static function getAuthenticationToken(): string
    {
        return (string)self::getExtensionConfiguration('authenticationToken');
    }

    public static function getAllowedMediaTypes(): string
    {
        return (string)self::getExtensionConfiguration('allowedMediaTypes');
    }

    public static function getBasicAuthUsername(): string
    {
        return (string)self::getExtensionConfiguration('basicAuthUsername');
    }

    public static function getBasicAuthPassword(): string
    {
        return (string)self::getExtensionConfiguration('basicAuthPassword');
    }

    public static function isDisabled(): bool
    {
        return (bool)self::getExtensionConfiguration('disable');
    }

    public static function isEnabled(): bool
    {
        return !self::isDisabled();
    }
}
