<?php

declare(strict_types=1);

namespace Zeroseven\CriticalCss\Service;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Action;
use TYPO3\CMS\Core\SysLog\Error;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LogService
{
    protected const TABLE = 'sys_log';

    protected static function write(string $message, int $code, int $type = null): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)?->getConnectionForTable(self::TABLE)->insert(self::TABLE, [
            'type' => $type ?: Type::ERROR,
            'error' => $code,
            'details' => $message,
            'action' => Action::UNDEFINED,
            'tstamp' => time()
        ]);
    }

    public static function message(string $message, int $type = null): void
    {
        self::write($message, Error::MESSAGE, $type);
    }

    public static function systemError(string $message, int $type = null): void
    {
        self::write($message, Error::SYSTEM_ERROR, $type);
    }

    public static function warning(string $message, int $type = null): void
    {
        self::write($message, Error::WARNING, $type);
    }
}
