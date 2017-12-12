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

//Module includes
include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonFinanceBudgetCycleID = $_POST['gibbonFinanceBudgetCycleID'];
$gibbonFinanceBudgetID = $_POST['gibbonFinanceBudgetID'];
$gibbonFinanceExpenseID = $_POST['gibbonFinanceExpenseID'];
$status = $_POST['status'];
$gibbonFinanceBudgetID2 = $_POST['gibbonFinanceBudgetID2'];
$status2 = $_POST['status2'];

if ($gibbonFinanceBudgetCycleID == '' or $gibbonFinanceBudgetID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenseRequest_manage_reimburse.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2";
    $URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenseRequest_manage.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_reimburse.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit();
    } else {
        if ($gibbonFinanceExpenseID == '' or $status == '' or $status != 'Paid' or empty($_FILES['file']['tmp_name'])) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
            exit();
        } else {
            //Get and check settings
            $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
            $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
            $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
            if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                $URL .= '&return=error0';
                header("Location: {$URL}");
                exit();
            } else {
                //Check if there are approvers
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() < 1) {
                    $URL .= '&return=error0';
                    header("Location: {$URL}");
                    exit();
                } else {
                    //Ready to go! Just check record exists and we have access, and load it ready to use...
                    try {
                        //Set Up filter wheres
                        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                        $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
							FROM gibbonFinanceExpense
							JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
							JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
							WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($result->rowCount() != 1) {
                        $URL .= '&return=error0';
                        header("Location: {$URL}");
                        exit();
                    } else {
                        $row = $result->fetch();

                        //Get relevant
                        $paymentDate = dateConvert($guid, $_POST['paymentDate']);
                        $paymentAmount = $_POST['paymentAmount'];
                        $gibbonPersonIDPayment = $_POST['gibbonPersonIDPayment'];
                        $paymentMethod = $_POST['paymentMethod'];

                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                        // Upload the file, return the /uploads relative path
                        $attachment = $fileUploader->uploadFromPost($file, $row['title']);

                        if (empty($attachment)) {
                            $URL .= '&return=error5';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Write back to gibbonFinanceExpense
                        try {
                            $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'status' => 'Paid', 'paymentDate' => $paymentDate, 'paymentAmount' => $paymentAmount, 'gibbonPersonIDPayment' => $gibbonPersonIDPayment, 'paymentMethod' => $paymentMethod, 'paymentReimbursementReceipt' => $attachment, 'paymentReimbursementStatus' => 'Requested');
                            $sql = 'UPDATE gibbonFinanceExpense SET status=:status, paymentDate=:paymentDate, paymentAmount=:paymentAmount, gibbonPersonIDPayment=:gibbonPersonIDPayment, paymentMethod=:paymentMethod, paymentReimbursementReceipt=:paymentReimbursementReceipt, paymentReimbursementStatus=:paymentReimbursementStatus WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID';
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Notify reimbursement officer that action is required
                        $reimbursementOfficer = getSettingByScope($connection2, 'Finance', 'reimbursementOfficer');
                        if ($reimbursementOfficer != false and $reimbursementOfficer != '') {
                            $notificationText = sprintf(__($guid, 'Someone has requested reimbursement for "%1$s" in budget "%2$s".'), $row['title'], $row['budget']);
                            setNotification($connection2, $guid, $reimbursementOfficer, $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_edit.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID2=".$row['gibbonFinanceBudgetID']);
                        }

                        //Write paid change to log
                        try {
                            $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'action' => 'Payment');
                            $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action=:action";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        //Write reimbursement request change to log
                        try {
                            $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'action' => 'Reimbursement Request');
                            $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action=:action";
                            $result = $connection2->prepare($sql);
                            $result->execute($data);
                        } catch (PDOException $e) {
                            $URL .= '&return=error2';
                            header("Location: {$URL}");
                            exit();
                        }

                        $URLSuccess .= '&return=success0';
                        header("Location: {$URLSuccess}");
                    }
                }
            }
        }
    }
}
