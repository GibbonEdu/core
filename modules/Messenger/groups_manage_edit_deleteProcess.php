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

use Gibbon\Domain\Messenger\GroupGateway;

include '../../gibbon.php';

$gibbonGroupID = $_GET['gibbonGroupID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

$address = $_POST['address'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/groups_manage_edit_delete.php&gibbonGroupID=$gibbonGroupID&gibbonPersonID=$gibbonPersonID";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/groups_manage_edit.php&gibbonGroupID=$gibbonGroupID";

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
        $values = $groupGateway->selectGroupByIDAndOwner($gibbonGroupID, $session->get('gibbonPersonID'));
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
