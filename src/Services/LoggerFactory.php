<?php
/**
 * Gibbon, Flexible & Open School System
 * Copyright (C) 2010, Gibbon Collaborative Team
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
     * @param string $channel
     * @return mixed
     */
    public function getLogger(string $channel = 'gibbon')

    {
        if (isset($this->loggerStack['gibbon']))
            return $this->loggerStack['gibbon'];
        $stream = new RotatingFileHandler(__DIR__.'/my_app.log', Logger::DEBUG);
    }
}