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

use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Staff\SubstituteGateway;

require_once '../../gibbon.php';

$search = $_GET['search'] ?? '';
$gibbonSubstituteID = $_POST['gibbonSubstituteID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Staff/substitutes_manage_edit.php&gibbonSubstituteID='.$gibbonSubstituteID.'&search='.$search;

if (isActionAccessible($guid, $connection2, '/modules/Staff/substitutes_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $subGateway = $container->get(SubstituteGateway::class);

    $data = [
        'active'         => $_POST['active'] ?? '',
        'type'           => $_POST['type'] ?? '',
        'details'        => $_POST['details'] ?? '',
        'priority'       => $_POST['priority'] ?? '',
    ];

    // Validate the required values are present
    if (empty($gibbonSubstituteID) || empty($data['active'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $sub = $subGateway->getByID($gibbonSubstituteID);
    $person = $container->get(UserGateway::class)->getByID($sub['gibbonPersonID']);

    if (empty($sub) || empty($person)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the substitute
    $updated = $subGateway->update($gibbonSubstituteID, $data);

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
