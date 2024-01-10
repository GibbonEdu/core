<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;
use Gibbon\Module\Staff\AbsenceNotificationProcess;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_add.php';
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_details.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $settingGateway = $container->get(SettingGateway::class);
    $fullDayThreshold =  floatval($settingGateway->getSettingByScope('Staff', 'absenceFullDayThreshold'));
    $halfDayThreshold = floatval($settingGateway->getSettingByScope('Staff', 'absenceHalfDayThreshold'));

    $dateStart = $_POST['dateStart'] ?? '';
    $dateEnd = $_POST['dateEnd'] ?? '';
    $notificationList = !empty($_POST['notificationList'])? explode(',', $_POST['notificationList']) : [];
    $schoolClosedOverride = $_POST['schoolClosedOverride'] ?? '';

    $data = [
        'gibbonSchoolYearID'       => $session->get('gibbonSchoolYearID'),
        'gibbonPersonID'           => $_POST['gibbonPersonID'] ?? '',
        'gibbonStaffAbsenceTypeID' => $_POST['gibbonStaffAbsenceTypeID'] ?? '',
        'reason'                   => $_POST['reason'] ?? '',
        'comment'                  => $_POST['comment'] ?? '',
        'commentConfidential'      => $_POST['commentConfidential'] ?? '',
        'status'                   => 'Approved',
        'coverageRequired'         => $_POST['coverageRequired'] ?? 'N',
        'gibbonPersonIDCreator'    => $session->get('gibbonPersonID'),
        'notificationSent'         => 'N',
        'notificationList'         => json_encode($notificationList),
        'gibbonGroupID'            => $_POST['gibbonGroupID'] ?? null,
    ];

    // Validate the required values are present
    if (empty($data['gibbonStaffAbsenceTypeID']) || empty($data['gibbonPersonID']) || empty($dateStart) || empty($dateEnd)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $type = $container->get(StaffAbsenceTypeGateway::class)->getByID($data['gibbonStaffAbsenceTypeID']);
    $person = $container->get(UserGateway::class)->getByID($data['gibbonPersonID']);

    if (empty($type) || empty($person)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Is approval required? Record the name of the approver & update the status.
    if ($type['requiresApproval'] == 'Y') {
        $data['gibbonPersonIDApproval'] = $_POST['gibbonPersonIDApproval'] ?? '';
        $data['status'] = 'Pending Approval';

        if (empty($data['gibbonPersonIDApproval'])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit;
        }
    }

    // Create the absence
    $gibbonStaffAbsenceID = $staffAbsenceGateway->insert($data);
    $gibbonStaffAbsenceID = str_pad($gibbonStaffAbsenceID, 14, '0', STR_PAD_LEFT);

    if (!$gibbonStaffAbsenceID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $start = new DateTime(Format::dateConvert($dateStart).' 00:00:00');
    $end = new DateTime(Format::dateConvert($dateEnd).' 23:00:00');

    $dateRange = new DatePeriod($start, new DateInterval('P1D'), $end);
    $partialFail = false;
    $absenceCount = 0;

    // Create separate dates within the absence time span
    foreach ($dateRange as $date) {
        $dateData = [
            'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
            'date'                 => $date->format('Y-m-d'),
            'allDay'               => $_POST['allDay'] ?? 'N',
            'timeStart'            => $_POST['timeStart'] ?? null,
            'timeEnd'              => $_POST['timeEnd'] ?? null,
        ];

        if ($dateData['allDay'] == 'Y') {
            $dateData['value'] = 1.0;
        } else {
            $start = new DateTime($date->format('Y-m-d').' '.$dateData['timeStart']);
            $end = new DateTime($date->format('Y-m-d').' '.$dateData['timeEnd']);

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

        if (!isSchoolOpen($guid, $dateData['date'], $connection2) && $schoolClosedOverride != 'Y') {
            continue;
        }

        if ($staffAbsenceDateGateway->unique($dateData, ['gibbonStaffAbsenceID', 'date'])) {
            $partialFail &= !$staffAbsenceDateGateway->insert($dateData);
            $absenceCount++;
        } else {
            $partialFail = true;
        }
    }

    if ($absenceCount == 0) {
        $URL .= '&return=error8';
        header("Location: {$URL}");
        exit;
    }

    // Start a background process for notifications
    $process = $container->get(AbsenceNotificationProcess::class);
    if ($type['requiresApproval'] == 'Y') {
        $process->startAbsencePendingApproval($gibbonStaffAbsenceID);
    } elseif ($data['coverageRequired'] != 'Y') {
        $process->startNewAbsence($gibbonStaffAbsenceID);
    }

    // Handle coverage request
    $canRequestCoverage = isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php');
    if ($data['coverageRequired'] == 'Y' && $canRequestCoverage) {
        $absenceWithCoverage = $gibbonStaffAbsenceID;
        require_once __DIR__ . '/coverage_requestProcess.php';
    }

    $URLSuccess .= "&gibbonStaffAbsenceID=$gibbonStaffAbsenceID";
    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}");
    exit;
}
