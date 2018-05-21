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

include '../../gibbon.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage_add.php";

if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $name = $_POST['name'];

    if ($name == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Create the group
        try {
            $data = array('gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'name' => $name, 'timestampCreated' => date('Y-m-d H:i:s', time()));
            $sql = 'INSERT INTO gibbonGroup SET gibbonPersonIDOwner=:gibbonPersonID, gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, timestampCreated=:timestampCreated';
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }
        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

        //Run through each of the selected participants.
        $update = true;
        $choices = null;
        if (isset($_POST['Members'])) {
            $choices = $_POST['Members'];
        }

        if (count($choices) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            foreach ($choices as $t) {
                try {
                    $data = array('gibbonGroupID' => $AI, 'gibbonPersonID' => $t);
                    $sql = 'INSERT INTO gibbonGroupPerson SET gibbonGroupID=:gibbonGroupID, gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $update = false;
                }
               
            }
            //Write to database
            if ($update == false) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
