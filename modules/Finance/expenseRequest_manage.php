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

use Gibbon\Forms\Form;

@session_start();

//Module includes
include './modules/Finance/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'My Expense Requests').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<p>';
    echo __($guid, 'This action allows you to create and manage expense requests, which will be submitted for approval to the relevant individuals. You will be notified when a request has been approved.').'<br/>';
    echo '</p>';

    //Check if have Full or Write in any budgets
    $budgets = getBudgetsByPerson($connection2, $_SESSION[$guid]['gibbonPersonID']);
    $budgetsAccess = false;
    if (is_array($budgets) && count($budgets)>0) {
        foreach ($budgets as $budget) {
            if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                $budgetsAccess = true;
            }
        }
    }
    if ($budgetsAccess == false) {
        echo "<div class='error'>";
        echo __($guid, 'You do not have Full or Write access to any budgets.');
        echo '</div>';
    } else {
        //Get and check settings
        $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
        $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            echo "<div class='error'>";
            echo __($guid, 'An error has occurred with your expense and budget settings.');
            echo '</div>';
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
                echo "<div class='error'>";
                echo __($guid, 'An error has occurred with your expense and budget settings.');
                echo '</div>';
            } else {
                //Ready to go!
                $gibbonFinanceBudgetCycleID = '';
                if (isset($_GET['gibbonFinanceBudgetCycleID'])) {
                    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
                }
                if ($gibbonFinanceBudgetCycleID == '') {
                    try {
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowcount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The Current budget cycle cannot be determined.');
                        echo '</div>';
                    } else {
                        $row = $result->fetch();
                        $gibbonFinanceBudgetCycleID = $row['gibbonFinanceBudgetCycleID'];
                        $gibbonFinanceBudgetCycleName = $row['name'];
                    }
                }
                if ($gibbonFinanceBudgetCycleID != '') {
                    try {
                        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                        $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($result->rowcount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The specified budget cycle cannot be determined.');
                        echo '</div>';
                    } else {
                        $row = $result->fetch();
                        $gibbonFinanceBudgetCycleName = $row['name'];
                    }

                    echo '<h2>';
                    echo $gibbonFinanceBudgetCycleName;
                    echo '</h2>';

                    echo "<div class='linkTop'>";
                        //Print year picker
                        $previousCycle = getPreviousBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                        if ($previousCycle != false) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$previousCycle."'>".__($guid, 'Previous Cycle').'</a> ';
                        } else {
                            echo __($guid, 'Previous Cycle').' ';
                        }
                        echo ' | ';
                        $nextCycle = getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                        if ($nextCycle != false) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$nextCycle."'>".__($guid, 'Next Cycle').'</a> ';
                        } else {
                            echo __($guid, 'Next Cycle').' ';
                        }
                    echo '</div>';

                    $status2 = null;
                    if (isset($_GET['status2'])) {
                        $status2 = $_GET['status2'];
                    }
                    $gibbonFinanceBudgetID2 = null;
                    if (isset($_GET['gibbonFinanceBudgetID2'])) {
                        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
                    }

                    echo '<h3>';
                    echo __($guid, 'Filters');
                    echo '</h3>';

                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

                    $form->setClass('noIntBorder fullWidth');

                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);
                    $form->addHiddenValue('q', "/modules/".$_SESSION[$guid]['module']."/expenseRequest_manage.php");

                    $statuses = array(
                        '' => __('All'),
                        'Requested' => __('Requested'),
                        'Approved' => __('Approved'),
                        'Rejected' => __('Rejected'),
                        'Cancelled' => __('Cancelled'),
                        'Ordered' => __('Ordered'),
                        'Paid' => __('Paid'),
                    );
                    $row = $form->addRow();
                        $row->addLabel('status2', __('Status'));
                        $row->addSelect('status2')->fromArray($statuses)->selected($status2);

                    $budgetsProcessed = array('' => __('All')) ;
                    foreach ($budgets as $budget) {
                        $budgetsProcessed[$budget[0]] = $budget[1];
                    }
                    $row = $form->addRow();
                        $row->addLabel('gibbonFinanceBudgetID2', __('Budget'));
                        $row->addSelect('gibbonFinanceBudgetID2')->fromArray($budgetsProcessed)->selected($gibbonFinanceBudgetID2);

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSearchSubmit($gibbon->session);

                    echo $form->getOutput();

                    try {
                        //Add in filter wheres
                        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                        $whereBudget = '';
                        if ($gibbonFinanceBudgetID2 != '') {
                            $data['gibbonFinanceBudgetID'] = $gibbonFinanceBudgetID2;
                            $whereBudget .= ' AND gibbonFinanceBudget.gibbonFinanceBudgetID=:gibbonFinanceBudgetID';
                        }
                        $whereStatus = '';
                        if ($status2 != '') {
                            $data['status'] = $status2;
                            $whereStatus .= ' AND status=:status';
                        }
                        //SQL for billing schedule AND pending
                        $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpense.gibbonPersonIDCreator=:gibbonPersonIDCreator $whereBudget $whereStatus";
                        $sql .= " ORDER BY FIND_IN_SET(status, 'Pending,Issued,Paid,Refunded,Cancelled'), timestampCreator DESC";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() < 1) {
                        echo '<h3>';
                        echo __($guid, 'View');
                        echo '</h3>';

                        echo "<div class='linkTop' style='text-align: right'>";
                        echo "<a style='margin-right: 3px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a><br/>";
                        echo '</div>';

                        echo "<div class='error'>";
                        echo __($guid, 'There are no records to display.');
                        echo '</div>';
                    } else {
                        echo '<h3>';
                        echo __($guid, 'View');
                        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__($guid, '%1$s expense requests in current view'), $result->rowCount()).'</span>';
                        echo '</h3>';

                        echo "<div class='linkTop'>";
                        echo "<a style='margin-right: 3px' href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module']."/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Add')."<img style='margin-left: 5px' title='".__($guid, 'Add')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_new.png'/></a><br/>";
                        echo '</div>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width: 110px'>";
                        echo __($guid, 'Title').'<br/>';
                        echo '</th>';
                        echo "<th style='width: 110px'>";
                        echo __($guid, 'Budget');
                        echo '</th>';
                        echo "<th style='width: 100px'>";
                        echo __($guid, 'Status')."<br/><span style='font-style: italic; font-size: 75%'>".__($guid, 'Reimbursement').'</span><br/>';
                        echo '</th>';
                        echo "<th style='width: 90px'>";
                        echo __($guid, 'Cost')."<br/><span style='font-style: italic; font-size: 75%'>(".$_SESSION[$guid]['currency'].')</span><br/>';
                        echo '</th>';
                        echo "<th style='width: 120px'>";
                        echo __($guid, 'Date');
                        echo '</th>';
                        echo "<th style='width: 140px'>";
                        echo __($guid, 'Actions');
                        echo '</th>';
                        echo '</tr>';

                        $count = 0;
                        $rowNum = 'odd';
                        while ($row = $result->fetch()) {
                            if ($count % 2 == 0) {
                                $rowNum = 'even';
                            } else {
                                $rowNum = 'odd';
                            }
                            ++$count;

                                //Color row by status
                                if ($row['status'] == 'Approved') {
                                    $rowNum = 'current';
                                }
                            if ($row['status'] == 'Rejected' or $row['status'] == 'Cancelled') {
                                $rowNum = 'error';
                            }

                            echo "<tr class=$rowNum>";
                            echo '<td>';
                            echo '<b>'.$row['title'].'</b><br/>';
                            echo '</td>';
                            echo '<td>';
                            echo $row['budget'];
                            echo '</td>';
                            echo '<td>';
                            echo $row['status'].'<br/>';
                            if ($row['paymentReimbursementStatus'] != '') {
                                echo "<span style='font-style: italic; font-size: 75%'>".$row['paymentReimbursementStatus'].'</span><br/>';
                            }
                            echo '</td>';
                            echo '<td>';
                            echo number_format($row['cost'], 2, '.', ',');
                            echo '</td>';
                            echo '<td>';
                            echo dateConvertBack($guid, substr($row['timestampCreator'], 0, 10));
                            echo '</td>';
                            echo '<td>';
                            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage_view.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__($guid, 'View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> ";
                            if ($row['status'] == 'Approved' and $row['purchaseBy'] == 'Self') {
                                echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage_reimburse.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__($guid, 'Request Reimbursement')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/gift.png'/></a> ";
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<input type="hidden" name="address" value="'.$_SESSION[$guid]['address'].'">';

                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>
