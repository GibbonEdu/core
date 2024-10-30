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

use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;
use Gibbon\Data\Validator;
use Gibbon\Domain\User\UserGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $settingGateway = $container->get(SettingGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    $fullDayThreshold =  floatval($settingGateway->getSettingByScope('Staff', 'coverageFullDayThreshold'));
    $fullDayThreshold = empty($fullDayThreshold) ? 8.0 : $fullDayThreshold;
    $internalCoverage = $settingGateway->getSettingByScope('Staff', 'coverageInternal');
    
    $requestDates = $_POST['requestDates'] ?? [];

    $data = [
        'gibbonSchoolYearID'     => $session->get('gibbonSchoolYearID'),
        'gibbonPersonIDStatus'   => $session->get('gibbonPersonID'),
        'gibbonPersonIDCoverage' => $_POST['gibbonPersonIDCoverage'] ?? null,
        'gibbonPersonID'         => $_POST['gibbonPersonID'] ?? '',
        'notesStatus'            => $_POST['notesStatus'] ?? '',
        'status'                 => $_POST['status'] ?? '',
        'requestType'            => 'Individual',
        'notificationSent'       => 'N',
    ];

    // Validate the required values are present
    if (empty($data['gibbonPersonIDCoverage']) || empty($requestDates)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $substitute = $container->get(SubstituteGateway::class)->selectBy(['gibbonPersonID'=> $data['gibbonPersonIDCoverage']])->fetch();
    $person = $container->get(UserGateway::class)->getByID($data['gibbonPersonIDCoverage']);
    
    if (($internalCoverage == 'N' && empty($substitute)) || empty($person)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Create the coverage request
    $gibbonStaffCoverageID = $staffCoverageGateway->insert($data);

    if (!$gibbonStaffCoverageID) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;
    $dateCount = 0;

    // Create separate dates within the coverage time span
    foreach ($requestDates as $date) {
        if (!isSchoolOpen($guid, $date, $connection2)) {
            continue;
        }

        $dateData = [
            'gibbonStaffCoverageID' => $gibbonStaffCoverageID,
            'date'                  => $date,
            'allDay'                => $_POST['allDay'] ?? 'N',
            'timeStart'             => $_POST['timeStart'] ?? null,
            'timeEnd'               => $_POST['timeEnd'] ?? null,
            'reason'                => $_POST['reason'] ?? '',
        ];

        if ($dateData['allDay'] == 'Y') {
            $dateData['value'] = 1.0;
        } else {
            $start = new DateTime($date.' '.$dateData['timeStart']);
            $end = new DateTime($date.' '.$dateData['timeEnd']);

            $timeDiff = $end->getTimestamp() - $start->getTimestamp();
            $hoursCovered = abs($timeDiff / 3600);
            
            if ($hoursCovered > $fullDayThreshold) {
                $dateData['value'] = 1.0;
            } else {
                $timeCalc = round($hoursCovered / $fullDayThreshold, 1);
                $dateData['value'] = max($timeCalc, 0.1);
            }
        }

        if ($staffCoverageDateGateway->unique($dateData, ['gibbonStaffCoverageID', 'date'])) {
            $partialFail &= !$staffCoverageDateGateway->insert($dateData);
            $dateCount++;
        } else {
            $partialFail = true;
        }
    }

    if ($dateCount == 0) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Send messages (Mail, SMS) to relevant users
    if ($data['status'] == 'Requested') {
        $process = $container->get(CoverageNotificationProcess::class);
        $process->startIndividualRequest($gibbonStaffCoverageID);
    }
    
    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URL}");
}
