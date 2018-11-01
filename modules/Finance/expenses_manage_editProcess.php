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

include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonFinanceBudgetCycleID = $_POST['gibbonFinanceBudgetCycleID'];
$gibbonFinanceBudgetID2 = $_POST['gibbonFinanceBudgetID2'];
$gibbonFinanceExpenseID = $_POST['gibbonFinanceExpenseID'];
$status2 = $_POST['status2'];
$countAgainstBudget = $_POST['countAgainstBudget'];
$status = $_POST['status'];

if ($gibbonFinanceBudgetCycleID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/expenses_manage_edit.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2&status2=$status2";

    if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
        if ($highestAction == false) {
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            if ($gibbonFinanceExpenseID == '' or $status == '' or $status == 'Please select...' or $countAgainstBudget == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Get and check settings
                $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
                $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
                $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
                if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    //Check if there are approvers
                    try {
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }

                    if ($result->rowCount() < 1) {
                        $URL .= '&return=error0';
                        header("Location: {$URL}");
                    } else {
                        //Ready to go! Just check record exists and we have access, and load it ready to use...
                        try {
                            //Set Up filter wheres
                            $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                            $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
								FROM gibbonFinanceExpense
								JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
								JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
								WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID";
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
                            $statusOld = $row['status'];

                            //Check if params are specified
                            if ($status == 'Paid' and ($row['status'] == 'Approved' or $row['status'] == 'Ordered')) {
                                $paymentDate = dateConvert($guid, $_POST['paymentDate']);
                                $paymentAmount = $_POST['paymentAmount'];
                                $gibbonPersonIDPayment = $_POST['gibbonPersonIDPayment'];
                                $paymentMethod = $_POST['paymentMethod'];
                                $paymentID = $_POST['paymentID'];
                            } else {
                                $paymentDate = $row['paymentDate'];
                                $paymentAmount = $row['paymentAmount'];
                                $gibbonPersonIDPayment = $row['gibbonPersonIDPayment'];
                                $paymentMethod = $row['paymentMethod'];
                                $paymentID = $row['paymentID'];
                            }

                            //Do Reimbursement work
                            $paymentReimbursementStatus = null;
                            $reimbursementComment = '';
                            if (isset($_POST['paymentReimbursementStatus'])) {
                                $paymentReimbursementStatus = $_POST['paymentReimbursementStatus'];
                                if ($paymentReimbursementStatus != 'Requested' and $paymentReimbursementStatus != 'Complete') {
                                    $paymentReimbursementStatus = null;
                                }
                                if ($row['status'] == 'Paid' and $row['purchaseBy'] == 'Self' and $row['paymentReimbursementStatus'] == 'Requested' and $paymentReimbursementStatus == 'Complete') {
                                    $paymentID = $_POST['paymentID'];
                                    $reimbursementComment = $_POST['reimbursementComment'];
                                    $notificationText = sprintf(__($guid, 'Your reimbursement expense request for "%1$s" in budget "%2$s" has been completed.'), $row['title'], $row['budget']);
                                    setNotification($connection2, $guid, $row['gibbonPersonIDCreator'], $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenseRequest_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=".$row['gibbonFinanceBudgetID']);
                                    //Write change to log
                                    try {
                                        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'action' => 'Reimbursement Completion', 'comment' => $reimbursementComment);
                                        $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action=:action, comment=:comment";
                                        $result = $connection2->prepare($sql);
                                        $result->execute($data);
                                    } catch (PDOException $e) {
                                        $URL .= '&return=error2';
                                        header("Location: {$URL}");
                                        exit();
                                    }
                                }
                            }

                            //Write back to gibbonFinanceExpense
                            try {
                                $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'status' => $status, 'countAgainstBudget' => $countAgainstBudget, 'paymentDate' => $paymentDate, 'paymentAmount' => $paymentAmount, 'gibbonPersonIDPayment' => $gibbonPersonIDPayment, 'paymentMethod' => $paymentMethod, 'paymentID' => $paymentID, 'paymentReimbursementStatus' => $paymentReimbursementStatus);
                                $sql = 'UPDATE gibbonFinanceExpense SET status=:status, countAgainstBudget=:countAgainstBudget, paymentDate=:paymentDate, paymentAmount=:paymentAmount, gibbonPersonIDPayment=:gibbonPersonIDPayment, paymentMethod=:paymentMethod, paymentID=:paymentID, paymentReimbursementStatus=:paymentReimbursementStatus WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            if ($statusOld != $status) {
                                $action = '';
                                if ($status == 'Requested') {
                                    $action = 'Request';
                                } elseif ($status == 'Approved') {
                                    $action = 'Approval - Exempt';
                                    //Notify original creator that it is approved
                                    $notificationText = sprintf(__($guid, 'Your expense request for "%1$s" in budget "%2$s" has been fully approved.'), $row['title'], $row['budget']);
                                    setNotification($connection2, $guid, $row['gibbonPersonIDCreator'], $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=".$row['gibbonFinanceBudgetID']);
                                } elseif ($status == 'Rejected') {
                                    $action = 'Rejection';
                                    //Notify original creator that it is rejected
                                    $notificationText = sprintf(__($guid, 'Your expense request for "%1$s" in budget "%2$s" has been rejected.'), $row['title'], $row['budget']);
                                    setNotification($connection2, $guid, $row['gibbonPersonIDCreator'], $notificationText, 'Finance', "/index.php?q=/modules/Finance/expenses_manage_view.php&gibbonFinanceExpenseID=$gibbonFinanceExpenseID&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status=&gibbonFinanceBudgetID=".$row['gibbonFinanceBudgetID']);
                                } elseif ($status == 'Ordered') {
                                    $action = 'Order';
                                } elseif ($status == 'Paid') {
                                    $action = 'Payment';
                                } elseif ($status == 'Cancelled') {
                                    $action = 'Cancellation';
                                }

                                //Write change to log
                                try {
                                    $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'action' => $action);
                                    $sql = "INSERT INTO gibbonFinanceExpenseLog SET gibbonFinanceExpenseID=:gibbonFinanceExpenseID, gibbonPersonID=:gibbonPersonID, timestamp='".date('Y-m-d H:i:s')."', action=:action";
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }
                            }

                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        }
                    }
                }
            }
        }
    }
}
