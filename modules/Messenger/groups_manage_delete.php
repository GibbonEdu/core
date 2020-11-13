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

use Gibbon\Forms\Prefab\DeleteForm;
use Gibbon\Domain\Messenger\GroupGateway;

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_delete.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonGroupID = (isset($_GET['gibbonGroupID']))? $_GET['gibbonGroupID'] : null;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    if ($gibbonGroupID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        $groupGateway = $container->get(GroupGateway::class);

        $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
        if ($highestAction == 'Manage Groups_all') {
            $result = $groupGateway->selectGroupByID($gibbonGroupID);
        } else {
            $result = $groupGateway->selectGroupByIDAndOwner($gibbonGroupID, $_SESSION[$guid]['gibbonPersonID']);
        }

        if ($result->isEmpty()) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $form = DeleteForm::createForm($_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module']."/groups_manage_deleteProcess.php?gibbonGroupID=$gibbonGroupID");
            echo $form->getOutput();
        }
    }
}
?>
