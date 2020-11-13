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
use Gibbon\Services\Format;
use Gibbon\Module\Finance\Tables\ExpenseLog;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_reimburse.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    
    $urlParams = compact('gibbonFinanceBudgetCycleID');        
        
    $page->breadcrumbs
        ->add(__('My Expense Requests'), 'expenseRequest_manage.php',  $urlParams)
        ->add(__('Request Reimbursement'));    

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    //Check if params are specified
    $gibbonFinanceExpenseID = $_GET['gibbonFinanceExpenseID'];
    $status2 = $_GET['status2'];
    $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
    if ($gibbonFinanceExpenseID == '' or $gibbonFinanceBudgetCycleID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
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
                //Ready to go! Just check record exists and we have access, and load it ready to use...
                
                    //Set Up filter wheres
                    $data = array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID, 'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID);
                    $sql = "SELECT gibbonFinanceExpense.*, gibbonFinanceBudget.name AS budget, surname, preferredName, 'Full' AS access
							FROM gibbonFinanceExpense
							JOIN gibbonFinanceBudget ON (gibbonFinanceExpense.gibbonFinanceBudgetID=gibbonFinanceBudget.gibbonFinanceBudgetID)
							JOIN gibbonPerson ON (gibbonFinanceExpense.gibbonPersonIDCreator=gibbonPerson.gibbonPersonID)
							WHERE gibbonFinanceBudgetCycleID=:gibbonFinanceBudgetCycleID AND gibbonFinanceExpenseID=:gibbonFinanceExpenseID AND gibbonFinanceExpense.status='Approved'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);

                if ($result->rowCount() != 1) {
                    echo "<div class='error'>";
                    echo __('The specified record cannot be found.');
                    echo '</div>';
                } else {
                    //Let's go!
                    $values = $result->fetch();

                    if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                        echo "<div class='linkTop'>";
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenseRequest_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__('Back to Search Results').'</a>';
                        echo '</div>';
                    }


                    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenseRequest_manage_reimburseProcess.php');

                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                    $form->addHiddenValue('status2', $status2);
                    $form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
                    $form->addHiddenValue('gibbonFinanceExpenseID', $gibbonFinanceExpenseID);
                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

                    $form->addRow()->addHeading(__('Basic Information'));

                    $cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
                    $row = $form->addRow();
                        $row->addLabel('nameBudget', __('Budget Cycle'));
                        $row->addTextField('nameBudget')->setValue($cycleName)->maxLength(20)->required()->readonly();

                    $form->addHiddenValue('gibbonFinanceBudgetID', $values['gibbonFinanceBudgetID']);
                    $row = $form->addRow();
                        $row->addLabel('budget', __('Budget'));
                        $row->addTextField('budget')->setValue($values['budget'])->maxLength(20)->required()->readonly();

                    $row = $form->addRow();
                        $row->addLabel('title', __('Title'));
                        $row->addTextField('title')->maxLength(60)->required()->readonly()->setValue($values['title']);

                    $row = $form->addRow();
                        $row->addLabel('status', __('Status'));
                        if ($values['status'] == 'Requested' or $values['status'] == 'Approved' or $values['status'] == 'Ordered') {
                            $statuses = array();
                            if ($values['status'] == 'Approved') {
                                $statuses['Paid'] = __('Paid');
                            }
                            $row->addSelect('status')->fromArray($statuses)->selected('Paid')->required();
                        } else {
                            $row->addTextField('status')->maxLength(60)->required()->readonly()->setValue($values['status']);
                        }

                    $row = $form->addRow();
                        $column = $row->addColumn();
                        $column->addLabel('body', __('Description'));
                        $column->addContent($values['body'])->setClass('fullWidth');

                    $row = $form->addRow();
                        $row->addLabel('cost', __('Total Cost'));
                        $row->addCurrency('cost')->required()->maxLength(15)->readonly()->setValue($values['cost']);

                    $row = $form->addRow();
                        $row->addLabel('countAgainstBudget', __('Count Against Budget'));
                        $row->addTextField('countAgainstBudget')->maxLength(3)->required()->readonly()->setValue(ynExpander($guid, $values['countAgainstBudget']));

                    $row = $form->addRow();
                        $row->addLabel('purchaseBy', __('Purchase By'));
                        $row->addTextField('purchaseBy')->required()->readonly()->setValue($values['purchaseBy']);

                    $row = $form->addRow();
                        $column = $row->addColumn();
                        $column->addLabel('purchaseDetails', __('Purchase Details'));
                        $column->addContent($values['purchaseDetails'])->setClass('fullWidth');

                    $form->addRow()->addHeading(__('Log'));

                    $expenseLog = $container->get(ExpenseLog::class)->create($gibbonFinanceExpenseID);
                    $form->addRow()->addContent($expenseLog->getOutput());

                    $row = $form->addRow();
                        $column = $row->addColumn();
                        $column->addLabel('comment', __('Comment'));
                        $column->addTextArea('comment')->setRows(8)->setClass('fullWidth');

                    $form->toggleVisibilityByClass('payment')->onSelect('status')->when('Paid');

                    $form->addRow()->addHeading(__('Payment Information'))->addClass('payment');

                    $row = $form->addRow()->addClass('payment');
                        $row->addLabel('paymentDate', __('Date Paid'))->description(__('Date of payment, not entry to system.'));
                        $row->addDate('paymentDate')->required();

                    $row = $form->addRow()->addClass('payment');
                    	$row->addLabel('paymentAmount', __('Amount paid'))->description(__('Final amount paid.'));
            			$row->addCurrency('paymentAmount')->required()->maxLength(15);

                    $form->addHiddenValue('gibbonPersonIDPayment', $_SESSION[$guid]['gibbonPersonID']);
                    $row = $form->addRow()->addClass('payment');
                        $row->addLabel('name', __('Payee'))->description(__('Staff who made, or arranged, the payment.'));
                        $row->addTextField('name')->required()->readonly()->setValue(Format::name('', ($_SESSION[$guid]['preferredName']), htmlPrep($_SESSION[$guid]['surname']), 'Staff', true, true));

                    $methods = array(
                        'Bank Transfer' => __('Bank Transfer'),
                        'Cash' => __('Cash'),
                        'Cheque' => __('Cheque'),
                        'Credit Card' => __('Credit Card'),
                        'Other' => __('Other')
                    );
                    $row = $form->addRow()->addClass('payment');
                        $row->addLabel('paymentMethod', __('Payment Method'));
                        $row->addSelect('paymentMethod')->fromArray($methods)->placeholder()->required();

                    $row = $form->addRow()->addClass('payment');;
                        $row->addLabel('file', __('Payment Receipt'))->description(__('Digital copy of the receipt for this payment.'));
                        $row->addFileUpload('file')
                            ->accepts('.jpg,.jpeg,.gif,.png,.pdf')
                            ->required();

                    $row = $form->addRow();
                        $row->addFooter();
                        $row->addSubmit();

                    echo $form->getOutput();

                }
            }
        }
    }
}
?>
