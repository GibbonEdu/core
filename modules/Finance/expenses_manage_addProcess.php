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

@session_start();

$gibbonFinanceBudgetCycleID = $_POST['gibbonFinanceBudgetCycleID'];
$gibbonFinanceBudgetID2 = $_POST['gibbonFinanceBudgetID2'];
$status2 = $_POST['status2'];

if ($gibbonFinanceBudgetCycleID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenses_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $allowExpenseAdd = getSettingByScope($connection2, 'Finance', 'allowExpenseAdd');
        if ($allowExpenseAdd != 'Y') {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            $gibbonFinanceBudgetID = $_POST['gibbonFinanceBudgetID'];
            $status = $_POST['status'];
            $title = $_POST['title'];
            $body = $_POST['body'];
            $cost = $_POST['cost'];
            $countAgainstBudget = $_POST['countAgainstBudget'];
            $purchaseBy = $_POST['purchaseBy'];
            $purchaseDetails = $_POST['purchaseDetails'];
            if ($status == 'Paid') {
                $paymentDate = dateConvert($guid, $_POST['paymentDate']);
                $paymentAmount = $_POST['paymentAmount'];
                $gibbonPersonIDPayment = $_POST['gibbonPersonIDPayment'];
                $paymentMethod = $_POST['paymentMethod'];
                $paymentID = $_POST['paymentID'];
            } else {
                $paymentDate = null;
                $paymentAmount = null;
                $gibbonPersonIDPayment = null;
                $paymentMethod = null;
                $paymentID = null;
            }

            if ($status == '' or $title == '' or $cost == '' or $countAgainstBudget == '' or $purchaseBy == '' or ($status == 'Paid' and ($paymentDate == '' or $paymentAmount == '' or $gibbonPersonIDPayment == '' or $paymentMethod == ''))) {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetID, 'title' => $title, 'body' => $body, 'status' => $status, 'statusApprovalBudgetCleared' => 'Y', 'cost' => $cost, 'countAgainstBudget' => $countAgainstBudget, 'purchaseBy' => $purchaseBy, 'purchaseDetails' => $purchaseDetails, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'paymentDate' => $paymentDate, 'paymentAmount' => $paymentAmount, 'gibbonPersonIDPayment' => $gibbonPersonIDPayment, 'paymentMethod' => $paymentMethod, 'paymentID' => $paymentID);
                    $sql = "INSERT INTO gibbonFinanceExpense SET gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID, title=:title, body=:body, status=:status, statusApprovalBudgetCleared=:statusApprovalBudgetCleared, cost=:cost, countAgainstBudget=:countAgainstBudget, purchaseBy=:purchaseBy, purchaseDetails=:purchaseDetails, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."', paymentDate=:paymentDate, paymentAmount=:paymentAmount, gibbonPersonIDPayment=:gibbonPersonIDPayment, paymentMethod=:paymentMethod, paymentID=:paymentID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                $gibbonFinanceExpenseID = $connection2->lastInsertID();

                //Add log entry
                try {
                    $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                    $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action='Approval - Exempt', comment=''";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

                //Add Payment log entry if needed
                if ($status == 'Paid') {
                    try {
                        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action='Payment', comment=''";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }
                }

                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
            }
        }
    }
}
