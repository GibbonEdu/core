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
use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\SubstituteGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;

require_once '../../gibbon.php';

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/coverage_view_accept.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID;
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/coverage_my.php';

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
        'gibbonPersonIDCoverage' => $_SESSION[$guid]['gibbonPersonID'],
        'timestampCoverage'      => date('Y-m-d H:i:s'),
        'notesCoverage'          => $_POST['notesCoverage'],
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
    $substitute = $container->get(SubstituteGateway::class)->getSubstituteByPerson($data['gibbonPersonIDCoverage']);

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

    // Remove any coverage dates from the coverage request if they were not selected
    foreach ($coverageDates as $date) {
        if (!in_array($date['date'], $requestDates)) {
            $uncoveredDates[] = $date['date'];
            $partialFail &= !$staffCoverageDateGateway->delete($date['gibbonStaffCoverageDateID']);
        }
    }

    // Send messages (Mail, SMS) to relevant users
    $process = $container->get(CoverageNotificationProcess::class);
    $process->startCoverageAccepted($gibbonStaffCoverageID, $uncoveredDates);


    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}");
}
