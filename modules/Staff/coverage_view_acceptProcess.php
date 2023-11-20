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

use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;
use Gibbon\Data\Validator;
use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_view_accept.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID;
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_my.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_view_accept.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    $requestDates = $_POST['coverageDates'] ?? [];

    $data = [
        'gibbonPersonIDCoverage' => $session->get('gibbonPersonID'),
        'timestampCoverage'      => date('Y-m-d H:i:s'),
        'notesCoverage'          => $_POST['notesCoverage'] ?? '',
        'status'                 => 'Accepted',
    ];

    // Validate the required values are present
    if (empty($gibbonStaffCoverageID) || empty($data['gibbonPersonIDCoverage']) || empty($requestDates)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $coverage = $staffCoverageGateway->getByID($gibbonStaffCoverageID);

    $internalCoverage = $container->get(SettingGateway::class)->getSettingByScope('Staff', 'coverageInternal');
    $substitute = $container->get(SubstituteGateway::class)->getSubstituteByPerson($data['gibbonPersonIDCoverage'], $internalCoverage);

    if (empty($coverage) || empty($substitute)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // If the coverage is for a particular absence, ensure this exists
    if (!empty($coverage['gibbonStaffAbsenceID'])) {
        $absence = $container->get(StaffAbsenceGateway::class)->getByID($coverage['gibbonStaffAbsenceID']);
        if (empty($absence)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
    }

    // Prevent two people accepting at the same time
    if ($coverage['status'] != 'Requested') {
        $URL .= '&return=warning3';
        header("Location: {$URL}");
        exit;
    }

    // Update the database
    $updated = $staffCoverageGateway->update($gibbonStaffCoverageID, $data);

    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;

    $coverageDates = $staffCoverageDateGateway->selectDatesByCoverage($gibbonStaffCoverageID);
    $uncoveredDates = [];
    $rerequestDates = [];

    // Remove any coverage dates from the coverage request if they were not selected
    foreach ($coverageDates as $date) {
        if (!in_array($date['gibbonStaffCoverageDateID'], $requestDates)) {
            $uncoveredDates[] = $date['date'];
            $rerequestDates[] = $staffCoverageDateGateway->getByID($date['gibbonStaffCoverageDateID']);
            $partialFail &= !$staffCoverageDateGateway->delete($date['gibbonStaffCoverageDateID']);
        }
    }

    // Send messages (Mail, SMS) to relevant users
    $process = $container->get(CoverageNotificationProcess::class);
    $process->startCoverageAccepted($gibbonStaffCoverageID, $uncoveredDates);

    // Create a new coverage request for incomplete broadcasts
    if ($coverage['requestType'] == 'Broadcast' && !empty($rerequestDates)) {
        $data = $coverage;

        $gibbonStaffCoverageID = $staffCoverageGateway->insert($data);

        // Create new coverage dates for unselected dates/times
        foreach ($rerequestDates as $data) {
            $data['gibbonStaffCoverageID'] = $gibbonStaffCoverageID;
            $partialFail &= !$staffCoverageDateGateway->insert($data);
        }
    }


    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}");
}
