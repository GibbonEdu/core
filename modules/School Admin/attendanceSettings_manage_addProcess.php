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

use Gibbon\Domain\Attendance\AttendanceCodeGateway;
use Gibbon\Data\Validator;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/attendanceSettings_manage_add.php";

if (isActionAccessible($guid, $connection2, '/modules/School Admin/attendanceSettings_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $attendanceCodeGateway = $container->get(AttendanceCodeGateway::class);

    $data = [
        'type'           => 'Additional',
        'name'           => $_POST['name'] ?? null,
        'nameShort'      => $_POST['nameShort'] ?? null,
        'direction'      => $_POST['direction'] ?? null,
        'scope'          => $_POST['scope'] ?? null,
        'sequenceNumber' => $_POST['sequenceNumber'] ?? null,
        'active'         => $_POST['active'] ?? null,
        'reportable'     => $_POST['reportable'] ?? null,
        'prefill'        => $_POST['prefill'] ?? null,
        'future'         => $_POST['future'] ?? null,
    ];

    $gibbonRoleIDArray = $_POST['gibbonRoleIDAll'] ?? '';
    $data['gibbonRoleIDAll'] = (is_array($gibbonRoleIDArray))? implode(',', $gibbonRoleIDArray) : $gibbonRoleIDArray;

    // Validate the required values are present
    if (empty($data['name']) || empty($data['nameShort']) || empty($data['direction']) || empty($data['scope']) || empty($data['sequenceNumber'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate that this record is unique
    if (!$attendanceCodeGateway->unique($data, ['name', 'nameShort'])) {
        $URL .= '&return=error7';
        header("Location: {$URL}");
        exit;
    }

    // Write to database
    $gibbonAttendanceCodeID = $attendanceCodeGateway->insert($data);

    $URL .= $gibbonAttendanceCodeID
        ? "&return=success0&editID=$gibbonAttendanceCodeID"
        : "&return=error2";

    header("Location: {$URL}");
}
