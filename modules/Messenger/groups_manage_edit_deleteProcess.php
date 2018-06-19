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

use Gibbon\Domain\Messenger\GroupGateway;

include '../../gibbon.php';

$gibbonGroupID = isset($_GET['gibbonGroupID'])? $_GET['gibbonGroupID'] : '';
$gibbonPersonID = isset($_GET['gibbonPersonID'])? $_GET['gibbonPersonID'] : '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage_edit_delete.php&gibbonGroupID=$gibbonGroupID&gibbonPersonID=$gibbonPersonID";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage_edit.php&gibbonGroupID=$gibbonGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else if (empty($gibbonGroupID) || empty($gibbonPersonID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $groupGateway = $container->get(GroupGateway::class);

    $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
    if ($highestAction == 'Manage Groups_all') {
        $values = $groupGateway->selectGroupByID($gibbonGroupID);
    } else {
        $values = $groupGateway->selectGroupByIDAndOwner($gibbonGroupID, $_SESSION[$guid]['gibbonPersonID']);
    }

    if (empty($values)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        $deleted = $groupGateway->deleteGroupPerson($gibbonGroupID, $gibbonPersonID);

        if (!$deleted) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
            exit;
        }
    }
}
