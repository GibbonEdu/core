<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Ross Parker
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * Created by PhpStorm.
 * Date: 22/01/2019
 * Time: 14:49
 */
namespace Gibbon\Services;

use Gibbon\Domain\System\SettingGateway;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Class LoggerFactory
 * @package Gibbon\Services
 */
class LoggerFactory
{
    /**
     * @var array
     */
    private $loggerStack;

    /**
     * @param string $channel
     * @return mixed
     */
    public function getLogger(string $channel = 'gibbon')

    {
        if (isset($this->loggerStack[$channel]))
            return $this->loggerStack[$channel];

        $stream = new RotatingFileHandler($this->getFilePath().$channel.'.log', $this->getKeepDays(), $this->getLoggerLevel());

        $logger = new Logger($channel, [$stream]);

        $this->loggerStack[$channel] = $logger;

        return $logger;
    }

    /**
     * @var int
     */
    private $keepDays = 7;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var int
     */
    private $loggerLevel = 100;

    /**
     * LoggerFactory constructor.
     * @param SettingGateway $settingGateway
     */
    public function __construct(SettingGateway $settingGateway)
    {
        $this->filePath = $settingGateway->getSettingByScope('System', 'absolutePath') . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR;
        $this->loggerLevel = $settingGateway->getSettingByScope('System', 'installType') === 'Production' ? Logger::WARNING : Logger::DEBUG;
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return LoggerFactory
     */
    public function setFilePath(string $filePath): LoggerFactory
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return int
     */
    public function getLoggerLevel(): int
    {
        return $this->loggerLevel;
    }

    /**
     * @return int
     */
    public function getKeepDays(): int
    {
        return $this->keepDays;
    }
}
