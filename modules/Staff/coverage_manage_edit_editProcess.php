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

use Gibbon\Domain\Staff\StaffCoverageDateGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffCoverageID = $_POST['gibbonStaffCoverageID'] ?? '';
$gibbonStaffCoverageDateID = $_POST['gibbonStaffCoverageDateID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Staff/coverage_manage_edit_edit.php&gibbonStaffCoverageID='.$gibbonStaffCoverageID.'&gibbonStaffCoverageDateID='.$gibbonStaffCoverageDateID;

if (isActionAccessible($guid, $connection2, '/modules/Staff/coverage_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} elseif (empty($gibbonStaffCoverageID) || empty($gibbonStaffCoverageDateID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $staffCoverageDateGateway = $container->get(StaffCoverageDateGateway::class);

    if (!$staffCoverageDateGateway->exists($gibbonStaffCoverageDateID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $data = [
        'allDay'    => $_POST['allDay'] ?? 'N',
        'timeStart' => $_POST['timeStart'] ?? null,
        'timeEnd'   => $_POST['timeEnd'] ?? null,
        'value'     => $_POST['value'] ?? '',
    ];

    $updated = $staffCoverageDateGateway->update($gibbonStaffCoverageDateID, $data);

    $URL .= !$updated
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
