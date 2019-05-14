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

use Gibbon\Services\Format;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;

require_once '../../gibbon.php';

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/coverage_availability.php&gibbonPersonID='.$gibbonPersonID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_availability.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonPersonID) || empty($_POST['dateStart'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $substituteGateway = $container->get(SubstituteGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    
    $substitute = $substituteGateway->selectBy(['gibbonPersonID' => $gibbonPersonID])->fetch();

    if (empty($substitute)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $dateStart = $_POST['dateStart'];
    $dateEnd = $_POST['dateEnd'] ?? $_POST['dateStart'];

    $start = new DateTime(Format::dateConvert($dateStart).' 00:00:00');
    $end = new DateTime(Format::dateConvert($dateEnd).' 23:00:00');

    $dateRange = new DatePeriod($start, new DateInterval('P1D'), $end);
    $partialFail = false;

    // Create separate exception dates within the time span
    foreach ($dateRange as $date) {
        $dateData = [
            'gibbonPersonIDUnavailable' => $gibbonPersonID,
            'reason'         => $_POST['reason'] ?? null,
            'date'           => $date->format('Y-m-d'),
            'allDay'         => $_POST['allDay'] ?? '',
            'timeStart'      => $_POST['timeStart'] ?? null,
            'timeEnd'        => $_POST['timeEnd'] ?? null,
        ];

        if (!isSchoolOpen($guid, $dateData['date'], $connection2)) {
            continue;
        }

        $partialFail &= !$staffCoverageDateGateway->insert($dateData);
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
    exit;
}
