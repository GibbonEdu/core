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

if (isActionAccessible($guid, $connection2, '/modules/School Admin/financeSettings.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    //Proceed!
    $page->breadcrumbs->add(__('Finance Settings'));

    $form = Form::create('financeSettings', $session->get('absoluteURL').'/modules/'.$session->get('module').'/financeSettingsProcess.php');

    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow()->addHeading('General Settings', __('General Settings'));

    $settingGateway = $container->get(SettingGateway::class);
    $setting = $settingGateway->getSettingByScope('Finance', 'email', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addEmail($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentEnabled', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->toggleVisibilityByClass('onlinePayment')->onSelect($setting['name'])->when('Y');

    $setting = $settingGateway->getSettingByScope('Finance', 'financeOnlinePaymentThreshold', true);
    $row = $form->addRow()->addClass('onlinePayment');
        $row->addLabel($setting['name'], __($setting['nameDisplay']))
            ->description(__($setting['description']))
            ->append(sprintf(__('In %1$s.'), $session->get('currency')));
        $row->addNumber($setting['name'])
            ->setValue($setting['value'])
            ->decimalPlaces(2);

    $row = $form->addRow()->addHeading('Invoices', __('Invoices'));

    $setting = $settingGateway->getSettingByScope('Finance', 'invoiceText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'invoiceNotes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $invoiceeNameStyle = array(
        'Surname, Preferred Name' => __('Surname') . ', ' . __('Preferred Name'),
        'Official Name' => __('Official Name')
    );
    $setting = $settingGateway->getSettingByScope('Finance', 'invoiceeNameStyle', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($invoiceeNameStyle)->selected($setting['value'])->required();

    $invoiceNumber = array(
        'Invoice ID' => __('Invoice ID'),
        'Person ID + Invoice ID' => __('Person ID')  . ' + ' . __('Invoice ID'),
        'Student ID + Invoice ID' => __('Student ID') . ' + ' . __('Invoice ID')
    );
    $setting = $settingGateway->getSettingByScope('Finance', 'invoiceNumber', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromArray($invoiceNumber)->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'paymentTypeOptions', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Receipts', __('Receipts'));

    $setting = $settingGateway->getSettingByScope('Finance', 'receiptText', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'receiptNotes', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'hideItemisation', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $row = $form->addRow()->addHeading('Reminders', __('Reminders'));

    $setting = $settingGateway->getSettingByScope('Finance', 'reminder1Text', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'reminder2Text', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'reminder3Text', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $row = $form->addRow()->addHeading('Expenses', __('Expenses'));

    $setting = $settingGateway->getSettingByScope('Finance', 'budgetCategories', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'expenseApprovalType', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelect($setting['name'])->fromString('One Of, Two Of, Chain Of All')->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'budgetLevelExpenseApproval', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'expenseRequestTemplate', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setValue($setting['value']);

    $setting = $settingGateway->getSettingByScope('Finance', 'allowExpenseAdd', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $setting = $settingGateway->getSettingByScope('Finance', 'purchasingOfficer', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])
            ->selected($setting['value'])
            ->placeholder('');

    $setting = $settingGateway->getSettingByScope('Finance', 'reimbursementOfficer', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectStaff($setting['name'])
            ->selected($setting['value'])
            ->placeholder('');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
