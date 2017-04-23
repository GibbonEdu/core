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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonFinanceBillingScheduleID = $_POST['gibbonFinanceBillingScheduleID'];
$search = $_GET['search'];

if ($gibbonFinanceBillingScheduleID == '' or $gibbonSchoolYearID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/billingSchedule_manage_edit.php&gibbonFinanceBillingScheduleID=$gibbonFinanceBillingScheduleID&gibbonSchoolYearID=$gibbonSchoolYearID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/billingSchedule_manage_edit.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonFinanceBillingScheduleID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                $sql = 'SELECT * FROM gibbonFinanceBillingSchedule WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID';
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
                $name = $_POST['name'];
                $active = $_POST['active'];
                $description = $_POST['description'];
                $invoiceIssueDate = $_POST['invoiceIssueDate'];
                $invoiceDueDate = $_POST['invoiceDueDate'];

                if ($name == '' or $active == '' or $invoiceIssueDate == '' or $invoiceDueDate == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'name' => $name, 'active' => $active, 'description' => $description, 'invoiceIssueDate' => dateConvert($guid, $invoiceIssueDate), 'invoiceDueDate' => dateConvert($guid, $invoiceDueDate), 'gibbonPersonIDUpdate' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonFinanceBillingScheduleID' => $gibbonFinanceBillingScheduleID);
                        $sql = "UPDATE gibbonFinanceBillingSchedule SET gibbonSchoolYearID=:gibbonSchoolYearID, name=:name, active=:active, description=:description, invoiceIssueDate=:invoiceIssueDate, invoiceDueDate=:invoiceDueDate, gibbonPersonIDUpdate=:gibbonPersonIDUpdate, timestampUpdate='".date('Y-m-d H:i:s')."' WHERE gibbonFinanceBillingScheduleID=:gibbonFinanceBillingScheduleID";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
