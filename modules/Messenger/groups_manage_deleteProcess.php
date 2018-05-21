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

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/Messenger/moduleFunctions.php';

$gibbonGroupID = $_GET['gibbonGroupID'];

if ($gibbonGroupID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage_delete.php&gibbonGroupID=$gibbonGroupID";
    $URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/groups_manage.php&gibbonGroupID=$gibbonGroupID";

    if (isActionAccessible($guid, $connection2, '/modules/Messenger/groups_manage_delete.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        try {
            $highestAction = getHighestGroupedAction($guid, '/modules/Messenger/groups_manage.php', $connection2);
            if ($highestAction == 'Manage Groups_all') {
                $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = 'SELECT gibbonGroupID FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID';
            }
            else {
                $data = array('gibbonGroupID' => $gibbonGroupID, 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = 'SELECT gibbonGroupID FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDOwner:gibbonPersonID';
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('gibbonGroupID' => $gibbonGroupID);
                $sql = 'DELETE FROM gibbonGroup WHERE gibbonGroupID=:gibbonGroupID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('gibbonGroupID' => $gibbonGroupID);
                $sql = 'DELETE FROM gibbonGroupPerson WHERE gibbonGroupID=:gibbonGroupID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
