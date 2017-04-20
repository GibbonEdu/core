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

$gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/budgetCycles_manage_edit.php&gibbonFinanceBudgetCycleID='.$gibbonFinanceBudgetCycleID;

if (isActionAccessible($guid, $connection2, '/modules/Finance/budgetCycles_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Check if school year specified
    if ($gibbonFinanceBudgetCycleID == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        try {
            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
            $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
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
            //Validate Inputs
            $name = $_POST['name'];
            $status = $_POST['status'];
            $sequenceNumber = $_POST['sequenceNumber'];
            $dateStart = dateConvert($guid, $_POST['dateStart']);
            $dateEnd = dateConvert($guid, $_POST['dateEnd']);

            if ($name == '' or $status == '' or $sequenceNumber == '' or is_numeric($sequenceNumber) == false or $dateStart == '' or $dateEnd == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Check unique inputs for uniquness
                try {
                    $data = array('name' => $name, 'sequenceNumber' => $sequenceNumber, 'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                    $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE (name=:name OR sequenceNumber=:sequenceNumber) AND NOT gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() > 0) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('name' => $name, 'status' => $status, 'sequenceNumber' => $sequenceNumber, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                        $sql = 'UPDATE gibbonFinanceBudgetCycle SET name=:name, status=:status, sequenceNumber=:sequenceNumber, dateStart=:dateStart, dateEnd=:dateEnd WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //UPDATE CYCLE ALLOCATION VALUES
                    $partialFail = false;
                    if (isset($_POST['values'])) {
                        $values = $_POST['values'];
                        $gibbonFinanceBudgetIDs = $_POST['gibbonFinanceBudgetIDs'];
                        $count = 0;
                        foreach ($values as $value) {
                            $failThis = false;

                            try {
                                $dataCheck = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetIDs[$count]);
                                $sqlCheck = 'SELECT * FROM gibbonFinanceBudgetCycleAllocation WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                                $resultCheck = $connection2->prepare($sqlCheck);
                                $resultCheck->execute($dataCheck);
                            } catch (PDOException $e) {
                                $partialFail = true;
                                $failThis = true;
                            }

                            if ($failThis == false) {
                                try {
                                    $data = array('value' => $value, 'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetIDs[$count]);
                                    if ($resultCheck->rowCount() == 0) { //INSERT
                                        $sql = 'INSERT INTO gibbonFinanceBudgetCycleAllocation SET value=:value, gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                                    } else { //UPDATE
                                        $sql = 'UPDATE gibbonFinanceBudgetCycleAllocation SET value=:value WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                                    }
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                            ++$count;
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
