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

require_once '../../gibbon.php';


$freeLearningUnitID = $_POST['freeLearningUnitID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/units_manage_delete.php&freeLearningUnitID=$freeLearningUnitID&gibbonDepartmentID=".$_REQUEST['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'].'&gibbonYearGroupIDMinimum='.$_GET['gibbonYearGroupIDMinimum'];
$URLDelete = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address']).'/units_manage.php&gibbonDepartmentID='.$_REQUEST['gibbonDepartmentID'].'&difficulty='.$_GET['difficulty'].'&name='.$_GET['name'].'&gibbonYearGroupIDMinimum='.$_GET['gibbonYearGroupIDMinimum'];

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage_delete.php') == false) {
    //Fail 0
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        //Fail 0
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Check existence of specified unit
        try {
            if ($highestAction == 'Manage Units_all') {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'SELECT * FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
            } else {
                $data = array('gibbonPersonID' => $session->get('gibbonPersonID'), 'freeLearningUnitID' => $freeLearningUnitID);
                $sql = "SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN gibbonDepartment ON (freeLearningUnit.gibbonDepartmentIDList LIKE CONCAT('%', gibbonDepartment.gibbonDepartmentID, '%')) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND freeLearningUnitID=:freeLearningUnitID UNION 
                SELECT DISTINCT freeLearningUnit.* FROM freeLearningUnit JOIN freeLearningUnitAuthor ON (freeLearningUnitAuthor.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitAuthor.gibbonPersonID=:gibbonPersonID AND freeLearningUnitAuthor.freeLearningUnitID=:freeLearningUnitID";
            }
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            //Fail 2
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }
        
        if ($result->rowCount() != 1) {
            //Fail 4
            $URL .= '&return=error4';
            header("Location: {$URL}");
        } else {
            //Write to database
            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'DELETE FROM freeLearningUnit WHERE freeLearningUnitID=:freeLearningUnitID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'DELETE FROM freeLearningUnitOutcome WHERE freeLearningUnitID=:freeLearningUnitID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'DELETE FROM freeLearningUnitBlock WHERE freeLearningUnitID=:freeLearningUnitID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            try {
                $data = array('freeLearningUnitID' => $freeLearningUnitID);
                $sql = 'DELETE FROM freeLearningUnitPrerequisite WHERE freeLearningUnitID=:freeLearningUnitID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                //Fail 2
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Success 0
            $URLDelete = $URLDelete.'&return=success0';
            header("Location: {$URLDelete}");
        }
    }
}
