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
use Gibbon\Forms\DatabaseFormFactory;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenses_manage_add.php', 'Manage Expenses_all') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $allowExpenseAdd = getSettingByScope($connection2, 'Finance', 'allowExpenseAdd');
    if ($allowExpenseAdd != 'Y') {
        echo "<div class='error'>";
        echo __('You do not have access to this action.');
        echo '</div>';
    } else {
        //Proceed!
        $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'];
    
        $urlParams = compact('gibbonFinanceBudgetCycleID');        
        
        $page->breadcrumbs
            ->add(__('Manage Expenses'), 'expenses_manage.php',  $urlParams)
            ->add(__('Add Expense'));

        echo "<div class='warning'>";
        echo __('Expenses added here do not require authorisation: this is for pre-authorised, or recurring expenses only.');
        echo '</div>';

        $editLink = '';
        if (isset($_GET['editID'])) {
            $editLink = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Finance/expenses_manage_edit.php&gibbonFinanceExpenseID='.$_GET['editID'].'&gibbonFinanceBudgetCycleID='.$_GET['gibbonFinanceBudgetCycleID'].'&status2='.$_GET['status2'].'&gibbonFinanceBudgetID2='.$_GET['gibbonFinanceBudgetID2'];
        }
        if (isset($_GET['return'])) {
            returnProcess($guid, $_GET['return'], $editLink, null);
        }

        //Check if school year specified
        $status2 = $_GET['status2'];
        $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'];
        if ($gibbonFinanceBudgetCycleID == '') {
            echo "<div class='error'>";
            echo __('You have not specified one or more required parameters.');
            echo '</div>';
        } else {
            if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                echo "<div class='linkTop'>";
                echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Finance/expenses_manage.php&gibbonFinanceBudgetCycleID=$gibbonFinanceBudgetCycleID&status2=$status2&gibbonFinanceBudgetID2=$gibbonFinanceBudgetID2'>".__('Back to Search Results').'</a>';
                echo '</div>';
			}
			
			$form = Form::create('expenseManage', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/expenses_manage_addProcess.php');
			$form->setFactory(DatabaseFormFactory::create($pdo));

			$form->addHiddenValue('address', $_SESSION[$guid]['address']);
			$form->addHiddenValue('status2', $status2);
            $form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);
			$form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

			$form->addRow()->addHeading(__('Basic Information'));
			
			$cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
			$row = $form->addRow();
				$row->addLabel('name', __('Budget Cycle'));
				$row->addTextField('name')->setValue($cycleName)->maxLength(20)->required()->readonly();

			$sql = "SELECT gibbonFinanceBudgetID as value, name FROM gibbonFinanceBudget WHERE active='Y' ORDER BY name";
			$row = $form->addRow();
				$row->addLabel('gibbonFinanceBudgetID', __('Budget'));
				$row->addSelect('gibbonFinanceBudgetID')->fromQuery($pdo, $sql)->required()->placeholder();

			$row = $form->addRow();
				$row->addLabel('title', __('Title'));
				$row->addTextField('title')->maxLength(60)->required();

			$statuses = array(
				'Approved' => __('Approved'),
				'Ordered' => __('Ordered'),
				'Paid' => __('Paid'),
			);
			$row = $form->addRow();
				$row->addLabel('status', __('Status'));
				$row->addSelect('status')->fromArray($statuses)->required()->placeholder();

			$expenseRequestTemplate = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate');
			$row = $form->addRow();
				$col = $row->addColumn();
				$col->addLabel('body', __('Description'));
				$col->addEditor('body', $guid)->setRows(15)->showMedia()->setValue($expenseRequestTemplate);

			$row = $form->addRow();
				$row->addLabel('cost', __('Total Cost'));
				$row->addCurrency('cost')->required()->maxLength(15);

			$row = $form->addRow();
				$row->addLabel('countAgainstBudget', __('Count Against Budget'))->description(__('For tracking purposes, should the item be counted against the budget? If immediately offset by some revenue, perhaps not.'));
				$row->addYesNo('countAgainstBudget')->required();

			$row = $form->addRow();
				$row->addLabel('purchaseBy', __('Purchase By'));
				$row->addSelect('purchaseBy')->fromArray(array('School' => __('School'), 'Self' => __('Self')))->required();

			$row = $form->addRow();
				$column = $row->addColumn();
				$column->addLabel('purchaseDetails', __('Purchase Details'));
				$column->addTextArea('purchaseDetails')->setRows(8)->setClass('fullWidth');

			$form->toggleVisibilityByClass('paymentInfo')->onSelect('status')->when('Paid');

			$form->addRow()->addHeading(__('Payment Information'))->addClass('paymentInfo');

			$row = $form->addRow()->addClass('paymentInfo');
				$row->addLabel('paymentDate', __('Date Paid'))->description(__('Date of payment, not entry to system.'));
				$row->addDate('paymentDate')->required();

			$row = $form->addRow()->addClass('paymentInfo');
				$row->addLabel('paymentAmount', __('Amount Paid'))->description(__('Final amount paid.'));
				$row->addCurrency('paymentAmount')->required()->maxLength(15);

			$row = $form->addRow()->addClass('paymentInfo');
				$row->addLabel('gibbonPersonIDPayment', __('Payee'))->description(__('Staff who made, or arranged, the payment.'));
				$row->addSelectStaff('gibbonPersonIDPayment')->required()->placeholder();

			$methods = array(
				'Bank Transfer' => __('Bank Transfer'),
				'Cash' => __('Cash'),
				'Cheque' => __('Cheque'),
				'Credit Card' => __('Credit Card'),
				'Other' => __('Other')
			);
			$row = $form->addRow()->addClass('paymentInfo');
				$row->addLabel('paymentMethod', __('Payment Method'));
				$row->addSelect('paymentMethod')->fromArray($methods)->placeholder()->required();

			$row = $form->addRow()->addClass('paymentInfo');
				$row->addLabel('paymentID', __('Payment ID'))->description(__('Transaction ID to identify this payment.'));
				$row->addTextField('paymentID')->maxLength(100);


			$row = $form->addRow();
				$row->addFooter();
				$row->addSubmit();

			echo $form->getOutput();
        }
    }
}

