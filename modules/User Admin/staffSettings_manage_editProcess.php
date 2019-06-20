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

use Gibbon\Domain\Staff\StaffAbsenceTypeGateway;

require_once '../../gibbon.php';

$gibbonStaffAbsenceTypeID = $_POST['gibbonStaffAbsenceTypeID'] ?? '';
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/User Admin/staffSettings_manage_edit.php&gibbonStaffAbsenceTypeID='.$gibbonStaffAbsenceTypeID;

if (isActionAccessible($guid, $connection2, '/modules/User Admin/staffSettings_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $staffAbsenceTypeGateway = $container->get(StaffAbsenceTypeGateway::class);

    $data = [
        'name'             => $_POST['name'] ?? '',
        'nameShort'        => $_POST['nameShort'] ?? '',
        'active'           => $_POST['active'] ?? 'Y',
        'reasons'          => $_POST['reasons'] ?? '',
        'sequenceNumber'   => $_POST['sequenceNumber'] ?? '',
        'requiresApproval' => $_POST['requiresApproval'] ?? 'N',
    ];

    if (empty($data['name']) || empty($data['nameShort'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if (!$staffAbsenceTypeGateway->exists($gibbonStaffAbsenceTypeID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    if (!$staffAbsenceTypeGateway->unique($data, ['name', 'nameShort'], $gibbonStaffAbsenceTypeID)) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    $updated = $staffAbsenceTypeGateway->update($gibbonStaffAbsenceTypeID, $data);

    $URL .= !$updated
        ? "&return=error1"
        : "&return=success0";
    header("Location: {$URL}");
}
