<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Domain\System;

use Gibbon\Domain\System\LogGateway;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * Adapts Monolog logger to log with the LogGateway.
 */
class LogHandler extends AbstractHandler implements HandlerInterface
{

    /**
     * @var \Gibbon\Domain\System\LogGateway
     */
    protected $logGateway;

    /**
     * Constructor
     */
    public function __construct(LogGateway $logGateway)
    {
        $this->logGateway = $logGateway;
        $this->setLevel(Logger::NOTICE);
    }

    /**
     * {@inheritDoc}
     */
    public function isHandling(array $record)
    {
        // Follow the old setLog function parameter validation.
        $context = $record['context'] ?? [];
        $array = $context['array'] ?? null;
        $gibbonSchoolYearID = $context['gibbonSchoolYearID'] ?? null;
        $title = $context['title'] ?? null;
        if (!is_array($array) && $array !== null) {
            return false;
        }
        if ($title === null || $gibbonSchoolYearID === null) {
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(array $record)
    {
        // Extracts context variables from record for logGateway
        // to log.
        $context = $record['context'] ?? [];
        $gibbonSchoolYearID = $context['gibbonSchoolYearID'] ?? null;
        $gibbonModuleID = $context ['gibbonModuleID'] ?? null;
        $gibbonPersonID = $context ['gibbonPersonID'] ?? null;
        $title = $context['title'] ?? null;
        $array = $context['array'] ?? null;
        $ip = $context['ip'] ?? null;

        // Note: what do we do with $record['message']?
        // Do we merge it into $array?

        $this->logGateway->addLog(
            $gibbonSchoolYearID, $gibbonModuleID, $gibbonPersonID, $title, $array, $ip);
    }

    /**
     * LogGateway compatable context for logging.
     *
     * @param string|null $gibbonSchoolYearID
     * @param string|null $gibbonModuleID
     * @param string|null $gibbonPersonID
     * @param string|null $title
     * @param array|null $array
     * @param string|null $ip
     * @return array
     */
    public static function context(
        ?string $gibbonSchoolYearID,
        ?string $gibbonModuleID,
        ?string $gibbonPersonID,
        ?string $title,
        ?array $array = null,
        ?string $ip = null
    ): array
    {
        return [
            'gibbonSchoolYearID' => $gibbonSchoolYearID,
            'gibbonModuleID' => $gibbonModuleID,
            'gibbonPersonID' => $gibbonPersonID,
            'title' => $title,
            'array' => $array,
            'ip' => $ip,
        ];
    }
}
