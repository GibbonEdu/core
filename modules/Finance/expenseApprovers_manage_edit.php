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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseApprovers_manage_edit.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Expense Approvers'),'expenseApprovers_manage.php')
        ->add(__('Edit Expense Approver'));

    //Check if gibbonFinanceExpenseApproverID specified
    $gibbonFinanceExpenseApproverID = $_GET['gibbonFinanceExpenseApproverID'] ?? '';
    if ($gibbonFinanceExpenseApproverID == '') {
        $page->addError(__('You have not specified one or more required parameters.'));
    } else {

            $data = array('gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID);
            $sql = 'SELECT * FROM gibbonFinanceExpenseApprover WHERE gibbonFinanceExpenseApproverID=:gibbonFinanceExpenseApproverID';
            $result = $connection2->prepare($sql);
            $result->execute($data);

        if ($result->rowCount() != 1) {
            $page->addError(__('The specified record cannot be found.'));
        } else {
            //Let's go!
            $values = $result->fetch();

            $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module')."/expenseApprovers_manage_editProcess.php?gibbonFinanceExpenseApproverID=$gibbonFinanceExpenseApproverID");
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $session->get('address'));

            $row = $form->addRow();
                $row->addLabel('gibbonPersonID', __('Staff'));
                $row->addSelectStaff('gibbonPersonID')->required()->placeholder();

            $expenseApprovalType = $container->get(SettingGateway::class)->getSettingByScope('Finance', 'expenseApprovalType');
            if ($expenseApprovalType == 'Chain Of All') {
                $row = $form->addRow();
                    $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique.'));
                    $row->addSequenceNumber('sequenceNumber', 'gibbonFinanceExpenseApprover', $values['sequenceNumber'])->required()->maxLength(3);
            }

            $form->loadAllValuesFrom($values);

            $row = $form->addRow();
                $row->addFooter();
                $row->addSubmit();

            echo $form->getOutput();
        }
    }
}
?>
