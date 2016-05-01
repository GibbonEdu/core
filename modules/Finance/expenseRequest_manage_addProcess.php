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

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonFinanceBudgetCycleID = $_POST['gibbonFinanceBudgetCycleID'];
$gibbonFinanceBudgetID = $_POST['gibbonFinanceBudgetID'];
$status = $_POST['status'];
$gibbonFinanceBudgetID2 = $_POST['gibbonFinanceBudgetID2'];
$status2 = $_POST['status2'];

if ($gibbonFinanceBudgetCycleID == '' or $gibbonFinanceBudgetID == '' or $status == '' or $status != 'Requested') {
    echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $title = $_POST['title'];
        $body = $_POST['body'];
        $cost = $_POST['cost'];
        $countAgainstBudget = $_POST['countAgainstBudget'];
        $purchaseBy = $_POST['purchaseBy'];
        $purchaseDetails = $_POST['purchaseDetails'];

        if ($title == '' or $cost == '' or $purchaseBy == '' or $countAgainstBudget == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            //Prepare approval settings
            $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
            if ($budgetLevelExpenseApproval == '') {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            } else {
                if ($budgetLevelExpenseApproval == 'N') { //Skip budget-level approval
                    $statusApprovalBudgetCleared = 'Y';
                } else {
                    $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID'], $gibbonFinanceBudgetID);
                    if (@$budgets[0][2] == 'Full') { //I can self-approve budget-level, as have Full access
                        $statusApprovalBudgetCleared = 'Y';
                    } else { //I cannot self-approve budget-level
                        $statusApprovalBudgetCleared = 'N';
                    }
                }
            }

            //Write to database
            try {
                $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceBudgetID' => $gibbonFinanceBudgetID, 'title' => $title, 'body' => $body, 'status' => $status, 'statusApprovalBudgetCleared' => $statusApprovalBudgetCleared, 'cost' => $cost, 'countAgainstBudget' => $countAgainstBudget, 'purchaseBy' => $purchaseBy, 'purchaseDetails' => $purchaseDetails, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "INSERT INTO gibbonFinanceExpense SET gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID, gibbonFinanceBudgetID=:gibbonFinanceBudgetID, title=:title, body=:body, status=:status, statusApprovalBudgetCleared=:statusApprovalBudgetCleared, cost=:cost, countAgainstBudget=:countAgainstBudget, purchaseBy=:purchaseBy, purchaseDetails=:purchaseDetails, gibbonPersonIDCreator=:gibbonPersonIDCreator, timestampCreator='".date('Y-m-d H:i:s')."'";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            $gibbonFinanceExpenseID = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

            //Add log entry
            try {
                $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action='Request', comment=''";
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                echo $e->getMessage();
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            //Last insert ID
            $AI = str_pad($connection2->lastInsertID(), 14, '0', STR_PAD_LEFT);

            //Do notifications
            $partialFail = false;
            if (setExpenseNotification($guid, $gibbonFinanceExpenseID, $gibbonFinanceBudgetCycleID, $connection2) == false) {
                $partialFail = true;
            }

            if ($partialFail == true) {
                $URL .= "&return=success1&editID=$AI";
                header("Location: {$URL}");
            } else {
                $URL .= "&return=success0&editID=$AI";
                header("Location: {$URL}");
            }
        }
    }
}
