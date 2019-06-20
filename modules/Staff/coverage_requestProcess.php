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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;

require_once '../../gibbon.php';

$gibbonStaffAbsenceID = $_POST['gibbonStaffAbsenceID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_request.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;
$URLSuccess = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_edit.php')
    ? $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_view_edit.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID
    : $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_view_details.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $fullDayThreshold =  floatval(getSettingByScope($connection2, 'Staff', 'absenceFullDayThreshold'));
    $halfDayThreshold = floatval(getSettingByScope($connection2, 'Staff', 'absenceHalfDayThreshold'));

    $requestDates = $_POST['requestDates'] ?? [];
    $substituteTypes = $_POST['substituteTypes'] ?? [];

    // Validate the database relationships exist
    $absence = $container->get(StaffAbsenceGateway::class)->getByID($gibbonStaffAbsenceID);

    if (empty($absence)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'gibbonStaffAbsenceID'   => $gibbonStaffAbsenceID,
        'gibbonSchoolYearID'     => $gibbon->session->get('gibbonSchoolYearID'),
        'gibbonPersonIDStatus'   => $gibbon->session->get('gibbonPersonID'),
        'gibbonPersonID'         => $absence['gibbonPersonID'],
        'gibbonPersonIDCoverage' => $_POST['gibbonPersonIDCoverage'] ?? null,
        'notesStatus'            => $_POST['notesStatus'] ?? '',
        'requestType'            => $_POST['requestType'] ?? '',
        'substituteTypes'        => implode(',', $substituteTypes),
        'status'                 => 'Requested',
        'notificationSent'       => 'N',
    ];

    // Validate the required values are present
    if (empty($data['gibbonStaffAbsenceID']) || !($data['requestType'] == 'Individual' || $data['requestType'] == 'Broadcast')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }
    
    if ($data['requestType'] == 'Individual') {
        // Return a custom error message if no dates have been selected
        if (empty($requestDates)) {
            $URL .= '&return=error8';
            header("Location: {$URL}");
            exit;
        }

        // Ensure the person is selected & exists for Individual coverage requests
        $personCoverage = $container->get(UserGateway::class)->getByID($data['gibbonPersonIDCoverage']);
        if (empty($personCoverage)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
    }

    // Create the coverage request
    $gibbonStaffCoverageID = $staffCoverageGateway->insert($data);
    $gibbonStaffCoverageID = str_pad($gibbonStaffCoverageID, 14, '0', STR_PAD_LEFT);

    if (!$gibbonStaffCoverageID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;

    $absenceDates = $staffAbsenceDateGateway->selectDatesByAbsence($data['gibbonStaffAbsenceID']);

    // Create a coverage date for each absence date, allow coverage request form to override absence times
    foreach ($absenceDates as $absenceDate) {
        // Skip any absence dates that have already been covered
        if (!empty($absenceDate['gibbonStaffCoverageID'])) {
            continue;
        }

        // Skip dates that were not selected for Individual requests
        if ($data['requestType'] == 'Individual' && !in_array($absenceDate['date'], $requestDates)) {
            continue;
        }

        $dateData = [
            'gibbonStaffCoverageID'    => $gibbonStaffCoverageID,
            'gibbonStaffAbsenceDateID' => $absenceDate['gibbonStaffAbsenceDateID'],
            'date'      => $absenceDate['date'],
            'allDay'    => $_POST['allDay'] ?? 'N',
            'timeStart' => $_POST['timeStart'] ?? $absenceDate['timeStart'],
            'timeEnd'   => $_POST['timeEnd'] ?? $absenceDate['timeEnd'],
        ];

        // Calculate the day 'value' of each date, based on thresholds from Staff Settings.
        if ($dateData['allDay'] == 'Y') {
            $dateData['value'] = 1.0;
        } else {
            $start = new DateTime($absenceDate['date'].' '.$dateData['timeStart']);
            $end = new DateTime($absenceDate['date'].' '.$dateData['timeEnd']);

            $timeDiff = $end->getTimestamp() - $start->getTimestamp();
            $hoursAbsent = abs($timeDiff / 3600);
            
            if ($hoursAbsent < $halfDayThreshold) {
                $dateData['value'] = 0.0;
            } elseif ($hoursAbsent < $fullDayThreshold) {
                $dateData['value'] = 0.5;
            } else {
                $dateData['value'] = 1.0;
            }
        }

        if ($staffCoverageDateGateway->unique($dateData, ['gibbonStaffCoverageID', 'date'])) {
            $partialFail &= !$staffCoverageDateGateway->insert($dateData);
        } else {
            $partialFail = true;
        }
    }

    // Send messages (Mail, SMS) to relevant users
    $process = $container->get(CoverageNotificationProcess::class);
    if ($data['requestType'] == 'Broadcast') {
        $process->startBroadcastRequest($gibbonStaffCoverageID);
    } else {
        $process->startIndividualRequest($gibbonStaffCoverageID);
    }
    
    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}&gibbonStaffCoverageID={$gibbonStaffCoverageID}");
}
