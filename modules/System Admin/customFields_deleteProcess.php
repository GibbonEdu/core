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

use Gibbon\Domain\System\CustomFieldGateway;

include '../../gibbon.php';

$gibbonCustomFieldID = $_GET['gibbonCustomFieldID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/customFields.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/customFields_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $customFieldGateway = $container->get(CustomFieldGateway::class);
    
    // Validate the required values are present
    if (empty($gibbonCustomFieldID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    if (!$customFieldGateway->exists($gibbonCustomFieldID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Delete the record
    $deleted = $customFieldGateway->delete($gibbonCustomFieldID);

    $URL .= !$deleted
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
