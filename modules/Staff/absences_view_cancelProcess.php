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

use Gibbon\Data\Validator;
use Gibbon\Domain\Staff\StaffAbsenceGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Module\Staff\CoverageNotificationProcess;
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Module\Staff\AbsenceNotificationProcess;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffAbsenceID = $_POST['gibbonStaffAbsenceID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_cancel.php&gibbonStaffAbsenceID='.$gibbonStaffAbsenceID;
$URLSuccess = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_view_byPerson.php';

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_view_byPerson.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $staffAbsenceGateway = $container->get(StaffAbsenceGateway::class);
    $staffAbsenceDateGateway = $container->get(StaffAbsenceDateGateway::class);
    $staffCoverageGateway = $container->get(StaffCoverageGateway::class);

    $data = [
        'comment' => $_POST['comment'] ?? '',
        'status'  => 'Cancelled',
    ];

    // Validate the required values are present
    if (empty($gibbonStaffAbsenceID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $absence = $staffAbsenceGateway->getAbsenceDetailsByID($gibbonStaffAbsenceID);
    if (empty($absence)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Prevent two people cancelling at the same time (?)
    if ($absence['status'] == 'Cancelled') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Update the database
    $updated = $staffAbsenceGateway->update($gibbonStaffAbsenceID, $data);
    if (!$updated) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // If the coverage is for a particular absence, ensure this exists
    $coverageList = [];
    if ($absence['coverageRequired'] == 'Y' || !empty($absence['coverage'])) {
        $coverageDates = $staffCoverageGateway->selectCoverageByAbsenceID($gibbonStaffAbsenceID)->fetchAll();

        // Set each coverage as cancelled
        foreach ($coverageDates as $coverage) {
            
            $coverageList[] = $coverage['gibbonStaffCoverageID'];
            $partialFail &= !$staffCoverageGateway->update($coverage['gibbonStaffCoverageID'], [
                'gibbonPersonIDStatus' => $session->get('gibbonPersonID'),
                'timestampStatus'      => date('Y-m-d H:i:s'),
                'status'               => 'Cancelled',
            ]);
        }
    }

    // Send messages (Mail, SMS) to relevant users
    if (!empty($coverageList)) {
        $process = $container->get(CoverageNotificationProcess::class);
        $process->startAbsenceWithCoverageCancelled($gibbonStaffAbsenceID, $coverageList);
    } else {
        $process = $container->get(AbsenceNotificationProcess::class);
        $process->startAbsenceCancelled($gibbonStaffAbsenceID);
    }

    $URLSuccess .= $partialFail
        ? "&return=warning1"
        : "&return=success0";

    header("Location: {$URLSuccess}");
}
