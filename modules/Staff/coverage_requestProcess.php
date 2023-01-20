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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffAbsenceID = $_POST['gibbonStaffAbsenceID'] ?? '';

$URL = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_request.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;
$URLSuccess = $gibbon->session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_details.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_request.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $fullDayThreshold =  floatval($settingGateway->getSettingByScope('Staff', 'coverageFullDayThreshold'));
    $coverageMode = $settingGateway->getSettingByScope('Staff', 'coverageMode');
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');

    $requestDates = $_POST['requestDates'] ?? [];
    $substituteTypes = $_POST['substituteTypes'] ?? [];
    $timetableClasses = $_POST['timetableClasses'] ?? [];

    $partialFail = false;

    // Validate the database relationships exist
    $absence = $container->get(StaffAbsenceGateway::class)->getByID($gibbonStaffAbsenceID);

    if (empty($absence)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $canSelectSubstitutes = $coverageMode == 'Requested' && $absence['status'] == 'Approved';

    $data = [
        'gibbonStaffAbsenceID'   => $gibbonStaffAbsenceID,
        'gibbonSchoolYearID'     => $gibbon->session->get('gibbonSchoolYearID'),
        'gibbonPersonIDStatus'   => $gibbon->session->get('gibbonPersonID'),
        'gibbonPersonID'         => $absence['gibbonPersonID'],
        'gibbonPersonIDCoverage' => $_POST['gibbonPersonIDCoverage'] ?? null,
        'notesStatus'            => $_POST['notesStatus'] ?? '',
        'requestType'            => $_POST['requestType'] ?? '',
        'substituteTypes'        => implode(',', $substituteTypes),
        'status'                 => $absence['status'] != 'Approved' || $coverageMode == 'Assigned' ? 'Pending' : 'Requested',
        'notificationSent'       => 'N',
    ];

    $gibbonPersonIDCoverageList = is_array($data['gibbonPersonIDCoverage']) ? $data['gibbonPersonIDCoverage'] : [$data['gibbonPersonIDCoverage']];
    $notesList = !empty($_POST['notes']) && is_array($_POST['notes']) ? $_POST['notes'] : [];

    // Validate the required values are present
    if (empty($data['gibbonStaffAbsenceID']) || !($data['requestType'] == 'Individual' || $data['requestType'] == 'Broadcast')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $requests = [];
    
    if ($data['requestType'] == 'Individual') {
        // Return a custom error message if no dates have been selected
        if (empty($requestDates) && empty($timetableClasses)) {
            $URL .= '&return=error8';
            header("Location: {$URL}");
            exit;
        }

        // Group coverage requests by person
        if (!empty($timetableClasses)) {
            // Get selected substitute per timetable class
            foreach ($timetableClasses as $contextCheckboxID) {
                $data['gibbonPersonIDCoverage'] = $gibbonPersonIDCoverageList[$contextCheckboxID] ?? null;

                // No substitute selected, create an open request for this class
                if (empty($data['gibbonPersonIDCoverage'])) {
                    $requests[] = ['data' => $data + ['requestType' => 'Broadcast'], 'periods' => [$contextCheckboxID]];
                    continue;
                }

                // Ensure the person is selected & exists for Individual coverage requests
                $personCoverage = $container->get(UserGateway::class)->getByID($data['gibbonPersonIDCoverage']);
                if (empty($personCoverage)) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }
                
                $requests[$data['gibbonPersonIDCoverage']]['data'] = $data;
                $requests[$data['gibbonPersonIDCoverage']]['periods'][] = $contextCheckboxID;
            }
        } else {
            // Get selected substitute per date
            foreach ($requestDates as $date) {
                $data['gibbonPersonIDCoverage'] = $gibbonPersonIDCoverageList[$date] ?? null;

                // No substitute selected, create an open request for this date
                if (empty($data['gibbonPersonIDCoverage'])) {
                    $requests[] = ['data' => $data + ['requestType' => 'Broadcast'], 'dates' => [$date]];
                    continue;
                }

                // Ensure the person is selected & exists for Individual coverage requests
                $personCoverage = $container->get(UserGateway::class)->getByID($data['gibbonPersonIDCoverage']);
                if (empty($personCoverage)) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }
                
                $requests[$data['gibbonPersonIDCoverage']]['data'] = $data;
                $requests[$data['gibbonPersonIDCoverage']]['dates'][] = $date;
            }
        }
        
    } else {
        // Add a single request for broadcasts
        $requests['broadcast'] = ['data' => $data, 'periods' => $timetableClasses, 'dates' => $requestDates];
    }

    // Create the coverage request(s)
    $coverageList = [];
    foreach ($requests as $index => $request) {
        $gibbonStaffCoverageID = $staffCoverageGateway->insert($request['data']);
        $requests[$index]['gibbonStaffCoverageID'] = str_pad($gibbonStaffCoverageID, 14, '0', STR_PAD_LEFT);
        $coverageList[] = str_pad($gibbonStaffCoverageID, 14, '0', STR_PAD_LEFT);

        $partialFail &= empty($gibbonStaffCoverageID);
    }

    // Get absence dates by date(
    $absenceDatesByDate = $staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($data['gibbonStaffAbsenceID'])->fetchAll();
    $absenceDatesByDate = array_reduce($absenceDatesByDate, function ($group, $item) {
        $group[$item['date']] = $item;
        return $group;
    }, []);

    // Create coverage data per request
    foreach ($requests as $request) {
        $absenceDates = [];

        // Get the specific absence dates and times that relate to this request
        if (!empty($request['periods'])) {
            // Get and merge timetable period times with absence times
            foreach ($request['periods'] as $contextCheckboxID) {
                list($date, $foreignTable, $foreignTableID) = explode(':', $contextCheckboxID);
                if (empty($absenceDatesByDate[$date])) continue;

                $times = $staffCoverageDateGateway->getCoverageTimesByForeignTable($foreignTable, $foreignTableID, $date);

                $absenceDates[] = array_merge($absenceDatesByDate[$date], $times, [
                    'gibbonStaffCoverageID'     => $request['gibbonStaffCoverageID'] ?? '',
                    'gibbonStaffCoverageDateID' => $request['gibbonStaffCoverageDateID'] ?? '',
                    'foreignTable'              => $foreignTable,
                    'foreignTableID'            => $foreignTableID,
                    'reason'                    => $notesList[$contextCheckboxID] ?? '',
                ]);
            }
        } elseif (!empty($request['dates'])) {
            // Get absence dates and times
            foreach ($request['dates'] as $date) {
                if (empty($absenceDatesByDate[$date])) continue;

                $absenceDates[] = array_merge($absenceDatesByDate[$date], ['reason' => $notesList[$date] ?? '']);
            }
        }

        // Create a coverage date for each absence date, allow coverage request form to override absence times
        foreach ($absenceDates as $absenceDate) {
            // TODO: Removed for Internal coverage, check for External coverage
            // Skip any absence dates that have already been covered
            // if (!empty($absenceDate['gibbonStaffCoverageID'])) {
            //     continue;
            // }

            $dateData = [
                'gibbonStaffCoverageID'    => $request['gibbonStaffCoverageID'],
                'gibbonStaffAbsenceDateID' => $absenceDate['gibbonStaffAbsenceDateID'],
                'foreignTable'             => $absenceDate['foreignTable'] ?? null,
                'foreignTableID'           => $absenceDate['foreignTableID'] ?? null,
                'date'                     => $absenceDate['date'],
                'allDay'                   => $_POST['allDay'] ?? 'N',
                'timeStart'                => $_POST['timeStart'] ?? $absenceDate['timeStart'],
                'timeEnd'                  => $_POST['timeEnd'] ?? $absenceDate['timeEnd'],
                'reason'                   => $absenceDate['reason'] ?? '',
            ];

            // Calculate the day 'value' of each date, based on thresholds from Staff Settings.
            if ($dateData['allDay'] == 'Y') {
                $dateData['value'] = 1.0;
            } else {
                $start = new DateTime($absenceDate['date'].' '.$dateData['timeStart']);
                $end = new DateTime($absenceDate['date'].' '.$dateData['timeEnd']);

                $timeDiff = $end->getTimestamp() - $start->getTimestamp();
                $hoursCovered = abs($timeDiff / 3600);
                
                if ($hoursCovered > $fullDayThreshold) {
                    $dateData['value'] = 1.0;
                } else {
                    $dateData['value'] = 0.5;
                }
            }

            if ($staffCoverageDateGateway->unique($dateData, ['gibbonStaffCoverageID', 'date', 'foreignTableID'])) {
                $partialFail &= !$staffCoverageDateGateway->insert($dateData);
            } else {
                $partialFail = true;
            }
        }

        // Send messages (Mail, SMS) to relevant users, per coverage request
        if ($canSelectSubstitutes) {
            $process = $container->get(CoverageNotificationProcess::class);
            if ($data['requestType'] == 'Broadcast') {
                $process->startBroadcastRequest($request['gibbonStaffCoverageID']);
            } else {
                $process->startIndividualRequest($request['gibbonStaffCoverageID']);
            }
        }
    }

    // Let users know about a new coverage request for an existing absence, update the absence
    if ($coverageMode == 'Assigned' || $absence['notificationSent'] == 'N') {
        $container->get(StaffAbsenceGateway::class)->update($gibbonStaffAbsenceID, ['coverageRequired' => 'Y']);

        $process = $container->get(CoverageNotificationProcess::class);
        $process->startNewCoverageRequest($coverageList);
    }
    
    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}");
}
