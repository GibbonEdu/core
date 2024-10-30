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

if (isActionAccessible($guid, $connection2, '/modules/Finance/expenseApprovers_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs
        ->add(__('Manage Expense Approvers'),'expenseApprovers_manage.php')
        ->add(__('Add Expense Approver'));

    $editLink = '';
    if (isset($_GET['editID'])) {
        $editLink = $session->get('absoluteURL').'/index.php?q=/modules/Finance/expenseApprovers_manage_edit.php&gibbonFinanceExpenseApproverID='.$_GET['editID'];
    }
    $page->return->setEditLink($editLink);


    $form = Form::create('action', $session->get('absoluteURL').'/modules/'.$session->get('module').'/expenseApprovers_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonPersonID', __('Staff'));
        $row->addSelectStaff('gibbonPersonID')->required()->placeholder();

    $expenseApprovalType = $container->get(SettingGateway::class)->getSettingByScope('Finance', 'expenseApprovalType');
    if ($expenseApprovalType == 'Chain Of All') {
        $row = $form->addRow();
            $row->addLabel('sequenceNumber', __('Sequence Number'))->description(__('Must be unique.'));
            $row->addSequenceNumber('sequenceNumber', 'gibbonFinanceExpenseApprover')->required()->maxLength(3);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
?>
