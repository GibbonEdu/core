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

use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonStaffAbsenceID = $_POST['gibbonStaffAbsenceID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/absences_manage_edit.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonStaffAbsenceID) || empty($_POST['date'])) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);

    $values = $staffAbsenceGateway->getByID($gibbonStaffAbsenceID);

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $date = Format::dateConvert($_POST['date']);

    if (!isSchoolOpen($guid, $date, $connection2)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
        'date'                 => $date,
        'allDay'               => $_POST['allDay'] ?? 'N',
        'timeStart'            => $_POST['timeStart'] ?? null,
        'timeEnd'              => $_POST['timeEnd'] ?? null,
    ];

    if ($staffAbsenceDateGateway->unique($data, ['gibbonStaffAbsenceID', 'date'])) {
        $inserted = $staffAbsenceDateGateway->insert($data);
    } else {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $URL .= !$inserted
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
    exit;
}
