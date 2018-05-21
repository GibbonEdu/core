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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage_add.php";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    //Validate Inputs
    $name = isset($_POST['name'])? $_POST['name'] : '';
    $choices = isset($_POST['members'])? $_POST['members'] : array();

    if (empty($name) || empty($choices)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $groupGateway = $container->get(GroupGateway::class);

        //Create the group
        $data = array('gibbonPersonIDOwner' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'name' => $name);
        $AI = $groupGateway->insertGroup($data);

        if (!$AI) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            //Run through each of the selected participants.
            foreach ($choices as $gibbonPersonID) {
                $data = array('gibbonGroupID' => $AI, 'gibbonPersonID' => $gibbonPersonID);
                $inserted = $groupGateway->insertGroupPerson($data);
                $partialFail &= !$inserted;
            }

            //Write to database
            if ($partialFail) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
                exit;
            } else {
                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
                exit;
            }
        }
    }
}
