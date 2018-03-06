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
include './modules/'.$_SESSION[$guid]['module'].'/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_view.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID']."'>".__($guid, 'My Expense Requests')."</a> > </div><div class='trailEnd'>".__($guid, 'View Expense Request').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null);
    }

    //Check if params are specified
    $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    $status = '';
    $status2 = $_GET['status2'];
    $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
    if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
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
                    //Ready to go! Just check record exists and we have access, and load it ready to use...
                    try {
                        $data = array('gibbonFinanceExpenseID' => $gibbonFinanceExpenseID, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget FROM gibbonFinanceExpense JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID) WHERE gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.gibbonPersonIDCreator=:gibbonPersonIDCreator';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }

                    if ($result->rowCount() != 1) {
                        echo "<div class='error'>";
                        echo __($guid, 'The specified record cannot be found.');
                        echo '</div>';
                    } else {
                        //Let's go!
                        $values = $result->fetch();
                        if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                            echo "<div class='linkTop'>";
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__($guid, 'Back to Search Results').'</a>';
                            echo '</div>';
                        }

                        $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage_viewProcess.php');

                        $form->setClass('smallIntBorder fullWidth');

                        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                        $form->addHiddenValue('status2', $status2);
                        $form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
                        $form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
                        $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);
                        $form->addHiddenValue('status', $status);

                        $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

                        $form->addRow()->addHeading(__('Basic Information'));

                        $cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
                        $row = $form->addRow();
                            $row->addLabel('name', __('Budget Cycle'));
                            $row->addTextField('name')->setValue($cycleName)->maxLength(20)->isRequired()->readonly();

                        $form->addHiddenValue('gibbonFinanceBudgetID', $values['gibbonFinanceBudgetID']);
                        $row = $form->addRow();
                            $row->addLabel('budget', __('Budget'));
                            $row->addTextField('budget')->setValue($cycleName)->maxLength(20)->isRequired()->readonly()->setValue($values['budget']);

                        $row = $form->addRow();
                            $row->addLabel('title', __('Title'));
                            $row->addTextField('title')->maxLength(60)->isRequired()->readonly()->setValue($values['title']);

                        $row = $form->addRow();
                            $row->addLabel('status', __('Status'));
                            $row->addTextField('status')->maxLength(60)->isRequired()->readonly()->setValue($values['status']);

                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addLabel('body', __('Description'));
                            $column->addContent($values['body'])->setClass('fullWidth');

                        $row = $form->addRow();
                        	$row->addLabel('cost', __('Total Cost'));
                			$row->addCurrency('cost')->isRequired()->maxLength(15)->readonly()->setValue($values['cost']);

                        $row = $form->addRow();
                            $row->addLabel('countAgainstBudget', __('Count Against Budget'));
                            $row->addTextField('countAgainstBudget')->maxLength(3)->isRequired()->readonly()->setValue(ynExpander($guid, $values['countAgainstBudget']));

                        $row = $form->addRow();
                            $row->addLabel('purchaseBy', __('Purchase By'));
                            $row->addTextField('purchaseBy')->isRequired()->readonly()->setValue($values['purchaseBy']);

                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addLabel('purchaseDetails', __('Purchase Details'));
                            $column->addContent($values['purchaseDetails'])->setClass('fullWidth');

                        $form->addRow()->addHeading(__('Log'));

                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addContent(getExpenseLog($guid, $gibbonFinanceExpenseID, $connection2));

                        $row = $form->addRow();
                            $column = $row->addColumn();
                            $column->addLabel('comment', __('Comment'));
                            $column->addTextArea('comment')->setRows(8)->setClass('fullWidth');

                        $row = $form->addRow();
                            $row->addFooter();
                            $row->addSubmit();

                        echo $form->getOutput();
                    }
                }
            }
        }
    }
}
?>
