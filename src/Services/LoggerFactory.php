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
     * getLogger
     * @param string $channel
     * @param array $options
     * @return mixed|Logger
     */
    public function getLogger(string $channel = 'gibbon', array $options = [])

    {
        if (isset($this->loggerStack[$channel]))
            return $this->loggerStack[$channel];

        $method = 'get' . ucfirst($channel) . 'Logger';
        if (method_exists($this, $method))
            return $this->$method();

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
    private $loggerLevel = Logger::DEBUG;

    /**
     * @var mixed|string
     */
    private $installType = 'Production';

    /**
     * LoggerFactory constructor.
     * @param string $filePath
     * @param string $installType
     */
    public function __construct(string $filePath, string $installType = 'Production')
    {
        $this->setFilePath($filePath . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR);
        $this->setInstallType($installType);
        $this->loggerLevel = $this->getInstallType() === 'Production' ? Logger::WARNING : Logger::DEBUG;
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

    /**
     * @var string
     */
    private $channel;

    /**
     * @return string
     */
    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * getInstallType
     * @return string
     */
    public function getInstallType(): string
    {
        return in_array($this->installType, ['Production', 'Testing', 'Development']) ? $this->installType : 'Production';
    }

    /**
     * setInstallType
     * @param string $installType
     * @return LoggerFactory
     */
    public function setInstallType(string $installType = 'Production'): LoggerFactory
    {
        $this->installType = in_array($installType, ['Production', 'Testing', 'Development']) ? $installType : 'Production';
        return $this;
    }

    /**
     * getMysqlLogger
     * Dynamic call ONLY
     * @return Logger
     */
    private function getMysqlLogger(): Logger
    {
        $streams = [];
        $streams[] = new RotatingFileHandler($this->getFilePath().'mysql.log', $this->getKeepDays(), $this->isProductionInstall() ? Logger::WARNING : Logger::DEBUG);
        $streams[] = new RotatingFileHandler($this->getFilePath().'gibbon.log', $this->getKeepDays(), $this->isProductionInstall() ? Logger::WARNING : Logger::DEBUG);

        $logger = new Logger('mysql', $streams);

        $this->loggerStack['mysql'] = $logger;

        return $logger;
    }

    /**
     * isProductionInstall
     * @return bool
     */
    private function isProductionInstall(): bool
    {
        return $this->getInstallType() === 'Production';
    }
}