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

use Gibbon\Domain\School\MedicalConditionGateway;

require_once '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/School Admin/medicalConditions_manage_add.php';

if (isActionAccessible($guid, $connection2, '/modules/School Admin/medicalConditions_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $medicalConditionGateway = $container->get(MedicalConditionGateway::class);

    $data = [
        'name'        => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
    ];

    // Validate the required values are present
    if (empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Create the substitute
    $gibbonMedicalConditionID = $medicalConditionGateway->insert($data);

    $URL .= !$gibbonMedicalConditionID
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}&editID=$gibbonMedicalConditionID");
}
