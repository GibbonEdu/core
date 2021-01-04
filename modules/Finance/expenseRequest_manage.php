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

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('My Expense Requests'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    echo '<p>';
    echo __('This action allows you to create and manage expense requests, which will be submitted for approval to the relevant individuals. You will be notified when a request has been approved.').'<br/>';
    echo '</p>';

    //Check if have Full or Write in any budgets
    $budgets = getBudgetsByPerson($connection2, $gibbon->session->get('gibbonPersonID'));
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
        echo __('You do not have Full or Write access to any budgets.');
        echo '</div>';
    } else {
        //Get and check settings
        $expenseApprovalType = getSettingByScope($connection2, 'Finance', 'expenseApprovalType');
        $budgetLevelExpenseApproval = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval');
        $expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
        if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
            echo "<div class='error'>";
            echo __('An error has occurred with your expense and budget settings.');
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
                echo __('An error has occurred with your expense and budget settings.');
                echo '</div>';
            } else {
                //Ready to go!
                $gibbonFinanceBudgetCycleID = '';
                if (isset($_GET['gibbonFinanceBudgetCycleID'])) {
                    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
                }
                if ($gibbonFinanceBudgetCycleID == '') {
                    
                        $data = array();
                        $sql = "SELECT * FROM gibbonFinanceBudgetCycle WHERE status='Current'";
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    if ($result->rowcount() != 1) {
                        echo "<div class='error'>";
                        echo __('The Current budget cycle cannot be determined.');
                        echo '</div>';
                    } else {
                        $row = $result->fetch();
                        $gibbonFinanceBudgetCycleID = $row['gibbonFinanceBudgetCycleID'];
                        $gibbonFinanceBudgetCycleName = $row['name'];
                    }
                }
                if ($gibbonFinanceBudgetCycleID != '') {
                    
                        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID);
                        $sql = 'SELECT * FROM gibbonFinanceBudgetCycle WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    if ($result->rowcount() != 1) {
                        echo "<div class='error'>";
                        echo __('The specified budget cycle cannot be determined.');
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
                            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$previousCycle."'>".__('Previous Cycle').'</a> ';
                        } else {
                            echo __('Previous Cycle').' ';
                        }
                        echo ' | ';
                        $nextCycle = getNextBudgetCycleID($gibbonFinanceBudgetCycleID, $connection2);
                        if ($nextCycle != false) {
                            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$nextCycle."'>".__('Next Cycle').'</a> ';
                        } else {
                            echo __('Next Cycle').' ';
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
                    echo __('Filters');
                    echo '</h3>';

                    $form = Form::create('action', $gibbon->session->get('absoluteURL').'/index.php', 'get');

                    $form->setClass('noIntBorder fullWidth');

                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);
                    $form->addHiddenValue('q', "/modules/".$gibbon->session->get('module')."/expenseRequest_manage.php");

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
                        $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonPersonIDCreator' => $gibbon->session->get('gibbonPersonID'));
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
                        echo __('View');
                        echo '</h3>';

                        echo "<div class='linkTop' style='text-align: right'>";
                        echo "<a style='margin-right: 3px' href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new.png'/></a><br/>";
                        echo '</div>';

                        echo "<div class='error'>";
                        echo __('There are no records to display.');
                        echo '</div>';
                    } else {
                        echo '<h3>';
                        echo __('View');
                        echo "<span style='font-weight: normal; font-style: italic; font-size: 55%'> ".sprintf(__('%1$s expense requests in current view'), $result->rowCount()).'</span>';
                        echo '</h3>';

                        echo "<div class='linkTop'>";
                        echo "<a style='margin-right: 3px' href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module')."/expenseRequest_manage_add.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/page_new.png'/></a><br/>";
                        echo '</div>';

                        echo "<table cellspacing='0' style='width: 100%'>";
                        echo "<tr class='head'>";
                        echo "<th style='width: 110px'>";
                        echo __('Title').'<br/>';
                        echo '</th>';
                        echo "<th style='width: 110px'>";
                        echo __('Budget');
                        echo '</th>';
                        echo "<th style='width: 100px'>";
                        echo __('Status')."<br/><span style='font-style: italic; font-size: 75%'>".__('Reimbursement').'</span><br/>';
                        echo '</th>';
                        echo "<th style='width: 90px'>";
                        echo __('Cost')."<br/><span style='font-style: italic; font-size: 75%'>(".$gibbon->session->get('currency').')</span><br/>';
                        echo '</th>';
                        echo "<th style='width: 120px'>";
                        echo __('Date');
                        echo '</th>';
                        echo "<th style='width: 140px'>";
                        echo __('Actions');
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
                            echo __($row['status']).'<br/>';
                            if ($row['paymentReimbursementStatus'] != '') {
                                echo "<span style='font-style: italic; font-size: 75%'>".__($row['paymentReimbursementStatus']).'</span><br/>';
                            }
                            echo '</td>';
                            echo '<td>';
                            echo number_format($row['cost'], 2, '.', ',');
                            echo '</td>';
                            echo '<td>';
                            echo dateConvertBack($guid, substr($row['timestampCreator'], 0, 10));
                            echo '</td>';
                            echo '<td>';
                            echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/expenseRequest_manage_view.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__('View')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/plus.png'/></a> ";
                            if ($row['status'] == 'Approved' and $row['purchaseBy'] == 'Self') {
                                echo "<a href='".$gibbon->session->get('absoluteURL').'/index.php?q=/modules/'.$gibbon->session->get('module').'/expenseRequest_manage_reimburse.php&gibbonFinanceExpenseID='.$row['gibbonFinanceExpenseID']."&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'><img title='".__('Request Reimbursement')."' src='./themes/".$gibbon->session->get('gibbonThemeName')."/img/gift.png'/></a> ";
                            }
                            echo '</td>';
                            echo '</tr>';
                        }
                        echo '<input type="hidden" name="address" value="'.$gibbon->session->get('address').'">';

                        echo '</table>';
                    }
                }
            }
        }
    }
}
?>
