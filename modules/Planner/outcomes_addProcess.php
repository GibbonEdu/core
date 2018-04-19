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

include './moduleFunctions.php';

$filter2 = '';
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/outcomes_add.php&filter2=$filter2";

if (isActionAccessible($guid, $connection2, '/modules/Planner/outcomes_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        if ($highestAction != 'Manage Outcomes_viewEditAll' and $highestAction != 'Manage Outcomes_viewAllEditLearningArea') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            //Proceed!
            $scope = $_POST['scope'];
            if ($scope == 'Learning Area') {
                $gibbonDepartmentID = $_POST['gibbonDepartmentID'];
            } else {
                $gibbonDepartmentID = null;
            }
            $name = $_POST['name'];
            $nameShort = $_POST['nameShort'];
            $active = $_POST['active'];
            $category = $_POST['category'];
            $description = $_POST['description'];
            $gibbonYearGroupIDList = isset($_POST['gibbonYearGroupIDList'])? $_POST['gibbonYearGroupIDList'] : array();
            $gibbonYearGroupIDList = implode(',', $gibbonYearGroupIDList);

            if ($scope == '' or ($scope == 'Learning Area' and $gibbonDepartmentID == '') or $name == '' or $nameShort == '' or $active == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('scope' => $scope, 'gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $name, 'nameShort' => $nameShort, 'active' => $active, 'category' => $category, 'description' => $description, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = 'INSERT INTO gibbonOutcome SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, nameShort=:nameShort, active=:active, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                $URL .= '&return=success0&editID='.$AI;
                header("Location: {$URL}");
            }
        }
    }
}
