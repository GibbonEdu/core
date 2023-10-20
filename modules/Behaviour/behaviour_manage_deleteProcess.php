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

include '../../gibbon.php';

$gibbonBehaviourID = $_POST['gibbonBehaviourID'] ?? '';
$address = $_POST['address'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$type = $_GET['type'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage_delete.php&gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $address, $connection2);
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

                $URLDelete = $URLDelete.'&return=success0';
                header("Location: {$URLDelete}");
            }
        }
    }
}
