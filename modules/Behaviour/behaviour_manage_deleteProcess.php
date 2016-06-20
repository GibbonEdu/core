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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonBehaviourID = $_POST['gibbonBehaviourID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/behaviour_manage_delete.php&gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=".$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/behaviour_manage.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        if ($gibbonBehaviourID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonBehaviourID' => $gibbonBehaviourID);
                $sql = 'SELECT * FROM gibbonBehaviour WHERE gibbonBehaviourID=:gibbonBehaviourID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $row = $result->fetch();

                //Write to database
                try {
                    $data = array('gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = 'DELETE FROM gibbonBehaviour WHERE gibbonBehaviourID=:gibbonBehaviourID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $return = deleteLike($connection2, 'Behaviour', 'gibbonBehaviourID', $gibbonBehaviourID, $_SESSION[$guid]['gibbonPersonID'], $row['gibbonPersonID'], 'Positive Behaviour');

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
