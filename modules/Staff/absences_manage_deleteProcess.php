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
use Gibbon\Domain\Staff\StaffAbsenceDateGateway;
use Gibbon\Domain\Staff\StaffCoverageGateway;
use Gibbon\Domain\Staff\StaffCoverageDateGateway;

$_POST['address'] = '/modules/Staff/absences_manage.php';
$gibbonStaffAbsenceID = $_GET['gibbonStaffAbsenceID'] ?? '';
$search = $_POST['search'] ?? '';

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/absences_manage.php&search='.$search;

if (isActionAccessible($guid, $connection2, '/modules/Staff/absences_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonStaffAbsenceID)) {
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

    $absenceDates = $staffAbsenceDateGateway->selectDatesByAbsenceWithCoverage($gibbonStaffAbsenceID)->fetchAll();
    $partialFail = false;

    // First delete any coverage attached to this absence
    $partialFail &= $container->get(StaffCoverageDateGateway::class)->deleteCoverageDatesByAbsenceID($gibbonStaffAbsenceID);
    $partialFail &= $container->get(StaffCoverageGateway::class)->deleteCoverageByAbsenceID($gibbonStaffAbsenceID);

    // Delete each absence date
    foreach ($absenceDates as $log) {
        $partialFail &= $staffAbsenceDateGateway->delete($log['gibbonStaffAbsenceDateID']);
    }

    // Then delete the absence itself
    $partialFail &= $staffAbsenceGateway->delete($gibbonStaffAbsenceID);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");
}
