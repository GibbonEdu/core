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

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

//Search & Filters
$search = null;
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}
$filter2 = null;
if (isset($_GET['filter2'])) {
    $filter2 = $_GET['filter2'];
}

$gibbonRubricID = $_GET['gibbonRubricID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/rubrics_duplicate.php&gibbonRubricID=$gibbonRubricID&search=$search&filter2=$filter2";

if (isActionAccessible($guid, $connection2, '/modules/Rubrics/rubrics_duplicate.php') == false) {
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
            //Check if school year specified
            if ($gibbonRubricID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    if ($highestAction == 'Manage Rubrics_viewEditAll') {
                        $data = array('gibbonRubricID' => $gibbonRubricID);
                        $sql = 'SELECT * FROM gibbonRubric WHERE gibbonRubricID=:gibbonRubricID';
                    } elseif ($highestAction == 'Manage Rubrics_viewAllEditLearningArea') {
                        $data = array('gibbonRubricID' => $gibbonRubricID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT * FROM gibbonRubric JOIN gibbonDepartment ON (gibbonRubric.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) AND NOT gibbonRubric.gibbonDepartmentID IS NULL WHERE gibbonRubricID=:gibbonRubricID AND (role='Coordinator' OR role='Teacher (Curriculum)') AND gibbonPersonID=:gibbonPersonID AND scope='Learning Area'";
                    }
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
                    //Proceed!
                    $scope = $_POST['scope'];
                    $gibbonDepartmentID = null;
                    if ($scope == 'Learning Area') {
                        $gibbonDepartmentID = $row['gibbonDepartmentID'];
                    }
                    $name = $_POST['name'];

                    if ($scope == '' or ($scope == 'Learning Area' and $gibbonDepartmentID == null) or $name == '') {
                        $URL .= '&return=error3';
                        header("Location: {$URL}");
                    } else {
                        //Write to database
                        try {
                            $data = array('scope' => $scope, 'gibbonDepartmentID' => $gibbonDepartmentID, 'name' => $name, 'active' => $row['active'], 'category' => $row['category'], 'description' => $row['description'], 'gibbonYearGroupIDList' => $row['gibbonYearGroupIDList'], 'gibbonScaleID' => $row['gibbonScaleID'], 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                            $sql = 'INSERT INTO gibbonRubric SET scope=:scope, gibbonDepartmentID=:gibbonDepartmentID, name=:name, active=:active, category=:category, description=:description, gibbonYearGroupIDList=:gibbonYearGroupIDList, gibbonScaleID=:gibbonScaleID, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Get last insert ID
                        $AI = str_pad($connection2->lastInsertID(), 8, '0', STR_PAD_LEFT);

                        $partialFail = false;

                        //INSERT ROWS
                        $rows = array();
                        try {
                            $dataFetch = array('gibbonRubricID' => $gibbonRubricID);
                            $sqlFetch = 'SELECT * FROM gibbonRubricRow WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber';
                            $resultFetch = $connection2->prepare($sqlFetch);
                            $resultFetch->execute($dataFetch);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowFetch = $resultFetch->fetch()) {
                            try {
                                $dataInsert = array('gibbonRubricID' => $AI, 'title' => $rowFetch['title'], 'sequenceNumber' => $rowFetch['sequenceNumber'], 'gibbonOutcomeID' => $rowFetch['gibbonOutcomeID']);
                                $sqlInsert = 'INSERT INTO gibbonRubricRow SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber, gibbonOutcomeID=:gibbonOutcomeID';
                                $resultInsert = $connection2->prepare($sqlInsert);
                                $resultInsert->execute($dataInsert);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            $rows[$rowFetch['gibbonRubricRowID']] = str_pad($connection2->lastInsertID(), 9, '0', STR_PAD_LEFT);
                        }

                        //INSERT COLUMNS
                        $columns = array();
                        try {
                            $dataFetch = array('gibbonRubricID' => $gibbonRubricID);
                            $sqlFetch = 'SELECT * FROM gibbonRubricColumn WHERE gibbonRubricID=:gibbonRubricID ORDER BY sequenceNumber';
                            $resultFetch = $connection2->prepare($sqlFetch);
                            $resultFetch->execute($dataFetch);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowFetch = $resultFetch->fetch()) {
                            try {
                                $dataInsert = array('gibbonRubricID' => $AI, 'title' => $rowFetch['title'], 'sequenceNumber' => $rowFetch['sequenceNumber'], 'gibbonScaleGradeID' => $rowFetch['gibbonScaleGradeID']);
                                $sqlInsert = 'INSERT INTO gibbonRubricColumn SET gibbonRubricID=:gibbonRubricID, title=:title, sequenceNumber=:sequenceNumber, gibbonScaleGradeID=:gibbonScaleGradeID';
                                $resultInsert = $connection2->prepare($sqlInsert);
                                $resultInsert->execute($dataInsert);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                            $columns[$rowFetch['gibbonRubricColumnID']] = str_pad($connection2->lastInsertID(), 9, '0', STR_PAD_LEFT);
                        }

                        //INSERT CELLS
                        try {
                            $dataFetch = array('gibbonRubricID' => $gibbonRubricID);
                            $sqlFetch = 'SELECT * FROM gibbonRubricCell WHERE gibbonRubricID=:gibbonRubricID';
                            $resultFetch = $connection2->prepare($sqlFetch);
                            $resultFetch->execute($dataFetch);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        while ($rowFetch = $resultFetch->fetch()) {
                            try {
                                $dataInsert = array('gibbonRubricID' => $AI, 'gibbonRubricColumnID' => $columns[$rowFetch['gibbonRubricColumnID']], 'gibbonRubricRowID' => $rows[$rowFetch['gibbonRubricRowID']], 'contents' => $rowFetch['contents']);
                                $sqlInsert = 'INSERT INTO gibbonRubricCell SET gibbonRubricID=:gibbonRubricID, gibbonRubricColumnID=:gibbonRubricColumnID, gibbonRubricRowID=:gibbonRubricRowID, contents=:contents';
                                $resultInsert = $connection2->prepare($sqlInsert);
                                $resultInsert->execute($dataInsert);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }

                        if ($partialFail == true) {
                            $URL .= '&return=warning1';
                            header("Location: {$URL}");
                        } else {
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
