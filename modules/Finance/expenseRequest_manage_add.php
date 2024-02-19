<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Http\Url;
use Gibbon\Forms\Form;
use Gibbon\Domain\System\SettingGateway;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseRequest_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $gibbonFinanceBudgetCycleID = $_GET['gibbonFinanceBudgetCycleID'] ?? '';

    $urlParams = compact('gibbonFinanceBudgetCycleID');

    $page->breadcrumbs
        ->add(__('My Expense Requests'), 'expenseRequest_manage.php',  $urlParams)
        ->add(__('Add Expense Request'));


    $page->return->addReturns(['success1' => __('Your request was completed successfully, but notifications could not be sent out.')]);

    //Check if gibbonFinanceBudgetCycleID specified
    $status2 = $_GET['status2'] ?? '';
    $gibbonFinanceBudgetID2 = $_GET['gibbonFinanceBudgetID2'] ?? '';
    if ($gibbonFinanceBudgetCycleID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {
        //Check if have Full or Write in any budgets
        $budgets = getBudgetsByPerson($connection2, $session->get('gibbonPersonID'));
        $budgetsAccess = false;
        if (is_array($budgets) && count($budgets)>0) {
            foreach ($budgets as $budget) {
                if ($budget[2] == 'Full' or $budget[2] == 'Write') {
                    $budgetsAccess = true;
                }
            }
        }
        if ($budgetsAccess == false) {
            $page->addError(__('You do not have Full or Write access to any budgets.'));
        } else {
            //Get and check settings
            $settingGateway = $container->get(SettingGateway::class);
            $expenseApprovalType = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType');
            $budgetLevelExpenseApproval = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval');
            $expenseRequestTemplate = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate');
            if ($expenseApprovalType == '' or $budgetLevelExpenseApproval == '') {
                $page->addError(__('An error has occurred with your expense and budget settings.'));
            } else {
                //Check if there are approvers
                try {
                    $data = array();
                    $sql = "SELECT * FROM gibbonFinanceExpenseApprover JOIN gibbonPerson ON (gibbonFinanceExpenseApprover.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE status='Full'";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                }

                if ($result->rowCount() < 1) {
                    $page->addError(__('An error has occurred with your expense and budget settings.'));
                } else {
                    //Ready to go!
                    if ($status2 != '' or $gibbonFinanceBudgetID2 != '') {
                        $params = [
                            "gibbonFinanceBudgetCycleID" => $gibbonFinanceBudgetCycleID,
                            "status2" => $status2,
                            "gibbonFinanceBudgetID2" =>$gibbonFinanceBudgetID2
                        ];
                        $page->navigator->addSearchResultsAction(Url::fromModuleRoute('Finance', 'expenseRequest_manage.php')->withQueryParams($params));
                    }

                    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/expenseRequest_manage_addProcess.php');

                    $form->addHiddenValue('address', $session->get('address'));
                    $form->addHiddenValue('status2', $status2);
                    $form->addHiddenValue('gibbonFinanceBudgetID2', $gibbonFinanceBudgetID2);

                    $form->addHiddenValue('gibbonFinanceBudgetCycleID', $gibbonFinanceBudgetCycleID);

                    $cycleName = getBudgetCycleName($gibbonFinanceBudgetCycleID, $connection2);
                    $row = $form->addRow();
                        $row->addLabel('name', __('Budget Cycle'));
                        $row->addTextField('name')->setValue($cycleName)->maxLength(20)->required()->readonly();

                    $budgetsProcessed = array() ;
                    foreach ($budgets as $budget) {
                        $budgetsProcessed[$budget[0]] = $budget[1];
                    }
                    $row = $form->addRow();
                        $row->addLabel('gibbonFinanceBudgetID', __('Budget'));
                        $row->addSelect('gibbonFinanceBudgetID')->fromArray($budgetsProcessed)->required()->placeholder();

                    $row = $form->addRow();
                        $row->addLabel('title', __('Title'));
                        $row->addTextField('title')->maxLength(60)->required();

                    $form->addHiddenValue('status', 'Requested');
                    $row = $form->addRow();
                        $row->addLabel('statusText', __('Status'));
                        $row->addTextField('statusText')->setValue(__('Requested'))->required()->readonly();

                    $expenseRequestTemplate = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate');
                    $row = $form->addRow();
    					$column = $row->addColumn();
    					$column->addLabel('body', __('Description'));
    					$column->addEditor('body', $guid)->setRows(15)->showMedia()->setValue($expenseRequestTemplate);

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
