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

@session_start();

use Library\Forms\Form;

if (isActionAccessible($guid, $connection2, '/modules/School Admin/financeSettings.php') == false) {
    //Acess denied
    echo "<div class='error'>";
    echo __($guid, 'You do not have access to this action.');
    echo '</div>';
} else {
    //Proceed!
    echo "<div class='trail'>";
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__($guid, 'Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__($guid, getModuleName($_GET['q']))."</a> > </div><div class='trailEnd'>".__($guid, 'Invoice & Receipt Settings').'</div>';
    echo '</div>';

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('financeSettings', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/financeSettingsProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow()->addHeading('General Settings');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'email', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addEmail($settingByScope['name'])->setValue($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'financeOnlinePaymentEnabled', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $form->toggleVisibilityByClass('onlinePayment')->onSelect($settingByScope['name'])->when('Y');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'financeOnlinePaymentThreshold', true);
    $row = $form->addRow()->addClass('onlinePayment');
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addNumber($settingByScope['name'])
            ->setValue($settingByScope['value'])
            ->decimalPlaces(2);

    $row = $form->addRow()->addHeading('Invoices');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'invoiceText', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'invoiceNotes', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'invoiceeNameStyle', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])->fromString('"Surname, Preferred Name", Official Name')->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'invoiceNumber', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])->fromString('Invoice ID, Person ID + Invoice ID, Student ID + Invoice ID')->selected($settingByScope['value'])->isRequired();

    $row = $form->addRow()->addHeading('Receipts');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'receiptText', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'receiptNotes', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'hideItemisation', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $row = $form->addRow()->addHeading('Reminders');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'reminder1Text', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'reminder2Text', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'reminder3Text', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $row = $form->addRow()->addHeading('Expenses');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'budgetCategories', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'expenseApprovalType', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelect($settingByScope['name'])->fromString('One Of, Two Of, Chain Of All')->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'budgetLevelExpenseApproval', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'expenseRequestTemplate', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addTextArea($settingByScope['name'])->setValue($settingByScope['value']);

    $settingByScope = getSettingByScope($connection2, 'Finance', 'allowExpenseAdd', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addYesNo($settingByScope['name'])->selected($settingByScope['value'])->isRequired();

    $settingByScope = getSettingByScope($connection2, 'Finance', 'purchasingOfficer', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelectStaff($pdo, $settingByScope['name'])
            ->selected($settingByScope['value'])
            ->placeholder('');

    $settingByScope = getSettingByScope($connection2, 'Finance', 'reimbursementOfficer', true);
    $row = $form->addRow();
        $row->addLabel($settingByScope['name'], $settingByScope['nameDisplay'])->description($settingByScope['description']);
        $row->addSelectStaff($pdo, $settingByScope['name'])
            ->selected($settingByScope['value'])
            ->placeholder('');

    $row = $form->addRow();
        $row->addContent('<span class="emphasis small">* '.__('denotes a required field').'</span>');
        $row->addSubmit();

    echo $form->getOutput();
}
