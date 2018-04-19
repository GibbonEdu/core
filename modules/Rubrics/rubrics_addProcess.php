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

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_add.php&search=$search&filter2=$filter2";
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_edit.php&sidebar=false&search=$search&filter2=$filter2";

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
    } else {
        if ($highestAction != 'Manage Rubrics_viewEditAll' and $highestAction != 'Manage Rubrics_viewAllEditLearningArea') {
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
            $active = $_POST['active'];
            $category = $_POST['category'];
            $description = $_POST['description'];
            $gibbonYearGroupIDList = isset($_POST['gibbonYearGroupIDList']) ? implode(',', $_POST['gibbonYearGroupIDList']) : '';
            $gibbonScaleID = null;
            if ($_POST['gibbonScaleID'] != '') {
                $gibbonScaleID = $_POST['gibbonScaleID'];
            }

            if ($scope == '' or ($scope == 'Learning Area' and $gibbonDepartmentID == '') or $name == '' or $active == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Lock table
                try {
                    $sql = 'LOCK TABLES gibbonRubric WRITE';
                    $result = $connection2->query($sql);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Get next autoincrement
                try {
                    $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonRubric'";
                    $resultAI = $connection2->query($sqlAI);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $rowAI = $resultAI->fetch();
                $AI = str_pad($rowAI['Auto_increment'], 8, '0', STR_PAD_LEFT);

                if ($AI == '') {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('scope' => $scope, 'gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $name, 'active' => $active, 'category' => $category, 'description' => $description, 'gibbonYearGroupIDList' => $gibbonYearGroupIDList, 'gibbonScaleID' => $gibbonScaleID, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'INSERT INTO gibbonRubric SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, active=:active, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList, gibbonScaleID=:gibbonScaleID, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Create rows & columns
                    for ($i = 1; $i <= $_POST['rows']; ++$i) {
                        try {
                            $data = array('gibbonRubricID' => $AI, 'title' => "Row $i", 'sequenceNumber' => $i);
                            $sql = 'INSERT INTO gibbonRubricRow SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                    }
                    for ($i = 1; $i <= $_POST['columns']; ++$i) {
                        try {
                            $data = array('gibbonRubricID' => $AI, 'title' => "Column $i", 'sequenceNumber' => $i);
                            $sql = 'INSERT INTO gibbonRubricColumn SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                        }
                    }

                    $URL = $URLSuccess."&return=success0&gibbonRubricID=$AI";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
