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

$gibbonStaffID = $_GET['gibbonStaffID'];
$search = $_GET['search'];

if ($gibbonStaffID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit_contract_add.php&gibbonStaffID=$gibbonStaffID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_contract_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStaffID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonStaffID' => $gibbonStaffID);
                $sql = 'SELECT gibbonStaffID, username FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID';
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
                $username = $row['username'];

                $title = $_POST['title'];
                $status = $_POST['status'];
                $dateStart = null;
                if (isset($_POST['dateStart'])) {
                    $dateStart = dateConvert($guid, $_POST['dateStart']);
                }
                $dateEnd = null;
                if (isset($_POST['dateEnd'])) {
                    if ($_POST['dateEnd'] != '') {
                        $dateEnd = dateConvert($guid, $_POST['dateEnd']);
                    }
                }
                $salaryScale = null;
                if (isset($_POST['salaryScale'])) {
                    $salaryScale = $_POST['salaryScale'];
                }
                $salaryAmount = null;
                if (isset($_POST['salaryAmount']) && $_POST['salaryAmount'] != '') {
                    $salaryAmount = $_POST['salaryAmount'];
                }
                $salaryPeriod = null;
                if (isset($_POST['salaryPeriod'])) {
                    $salaryPeriod = $_POST['salaryPeriod'];
                }
                $responsibility = null;
                if (isset($_POST['responsibility'])) {
                    $responsibility = $_POST['responsibility'];
                }
                $responsibilityAmount = null;
                if (isset($_POST['responsibilityAmount']) && $_POST['responsibilityAmount'] != '') {
                    $responsibilityAmount = $_POST['responsibilityAmount'];
                }
                $responsibilityPeriod = null;
                if (isset($_POST['responsibilityPeriod'])) {
                    $responsibilityPeriod = $_POST['responsibilityPeriod'];
                }
                $housingAmount = null;
                if (isset($_POST['housingAmount']) && $_POST['housingAmount'] != '') {
                    $housingAmount = $_POST['housingAmount'];
                }
                $housingPeriod = null;
                if (isset($_POST['housingPeriod'])) {
                    $housingPeriod = $_POST['housingPeriod'];
                }
                $travelAmount = null;
                if (isset($_POST['travelAmount']) && $_POST['travelAmount'] != '') {
                    $travelAmount = $_POST['travelAmount'];
                }
                $travelPeriod = null;
                if (isset($_POST['travelPeriod'])) {
                    $travelPeriod = $_POST['travelPeriod'];
                }
                $retirementAmount = null;
                if (isset($_POST['retirementAmount']) && $_POST['retirementAmount'] != '') {
                    $retirementAmount = $_POST['retirementAmount'];
                }
                $retirementPeriod = null;
                if (isset($_POST['retirementPeriod'])) {
                    $retirementPeriod = $_POST['retirementPeriod'];
                }
                $bonusAmount = null;
                if (isset($_POST['bonusAmount']) && $_POST['bonusAmount'] != '') {
                    $bonusAmount = $_POST['bonusAmount'];
                }
                $bonusPeriod = null;
                if (isset($_POST['bonusPeriod'])) {
                    $bonusPeriod = $_POST['bonusPeriod'];
                }
                $education = null;
                if (isset($_POST['education'])) {
                    $education = $_POST['education'];
                }
                $notes = null;
                if (isset($_POST['notes'])) {
                    $notes = $_POST['notes'];
                }

                $contractUpload = null;
                if ($_FILES['file1']['tmp_name'] != '') {
                    $time = time();
                    //Check for folder in uploads based on today's date
                    $path = $_SESSION[$guid]['absolutePath'];
                    if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                        mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
                    }
                    //Move 240 attached file, if there is one
                    if ($_FILES['file1']['tmp_name'] != '') {
                        $unique = false;
                        $count = 0;
                        while ($unique == false and $count < 100) {
                            $suffix = randomPassword(16);
                            if ($count == 0) {
                                $contractUpload = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.$username.'_'.$suffix.strrchr($_FILES['file1']['name'], '.');
                            } else {
                                $contractUpload = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.$username.''."_$count_".$suffix.strrchr($_FILES['file1']['name'], '.');
                            }

                            if (!(file_exists($path.'/'.$contractUpload))) {
                                $unique = true;
                            }
                            ++$count;
                        }
                        if (!(move_uploaded_file($_FILES['file1']['tmp_name'], $path.'/'.$contractUpload))) {
                            $contractUpload = '';
                            $imageFail = true;
                        }
                    } else {
                        $contractUpload = '';
                    }
                }

                if ($title == '' or $status == '') {
                    $URL .= '&return=error1&step=1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonStaffID' => $gibbonStaffID, 'title' => $title, 'status' => $status, 'dateStart' => $dateStart, 'dateEnd' => $dateEnd, 'salaryScale' => $salaryScale, 'salaryAmount' => $salaryAmount, 'salaryPeriod' => $salaryPeriod, 'responsibility' => $responsibility, 'responsibilityAmount' => $responsibilityAmount, 'responsibilityPeriod' => $responsibilityPeriod, 'housingAmount' => $housingAmount, 'housingPeriod' => $housingPeriod, 'travelAmount' => $travelAmount, 'travelPeriod' => $travelPeriod, 'retirementAmount' => $retirementAmount, 'retirementPeriod' => $retirementPeriod, 'bonusAmount' => $bonusAmount, 'bonusPeriod' => $bonusPeriod, 'education' => $education, 'notes' => $notes, 'contractUpload' => $contractUpload, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'INSERT INTO gibbonStaffContract SET gibbonStaffID=:gibbonStaffID, title=:title, status=:status, dateStart=:dateStart, dateEnd=:dateEnd, salaryScale=:salaryScale, salaryAmount=:salaryAmount, salaryPeriod=:salaryPeriod, responsibility=:responsibility, responsibilityAmount=:responsibilityAmount, responsibilityPeriod=:responsibilityPeriod, housingAmount=:housingAmount, housingPeriod=:housingPeriod, travelAmount=:travelAmount, travelPeriod=:travelPeriod, retirementAmount=:retirementAmount, retirementPeriod=:retirementPeriod, bonusAmount=:bonusAmount, bonusPeriod=:bonusPeriod, education=:education, notes=:notes, contractUpload=:contractUpload, gibbonPersonIDCreator=:gibbonPersonIDCreator';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Last insert ID
                    $AI = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

                    $URL .= "&return=success0&editID=$AI";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
