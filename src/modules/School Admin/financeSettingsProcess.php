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

namespace Module\School_Admin ;

use Gibbon\core\post ;

if (! $this instanceof post) die();

$URL = array('q'=>'/modules/School Admin/financeSettings.php');

if (! $this->getSecurity()->isActionAccessible('/modules/School Admin/financeSettings.php')) {
    $this->insertMessage('return.error.0');
    $this->redirect($URL);
} else {
    //Proceed!

    if (empty($_POST['email'])
		|| empty($_POST['financeOnlinePaymentEnabled'])
		|| empty($_POST['invoiceNumber'])
		|| empty($_POST['hideItemisation'])
		|| empty($_POST['budgetCategories'])
		|| empty($_POST['expenseApprovalType'])
		|| empty($_POST['budgetLevelExpenseApproval'])
		|| empty($_POST['allowExpenseAdd']))
	{
        $this->insertMessage('return.error.1');
        $this->redirect($URL);
    } else {
        //Write to database
        $fail = false;

		if (! $this->config->setSettingByScope('email', $_POST['email'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('financeOnlinePaymentEnabled', $_POST['financeOnlinePaymentEnabled'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('financeOnlinePaymentThreshold', $_POST['financeOnlinePaymentThreshold'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('invoiceText', $_POST['invoiceText'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('invoiceNotes', $_POST['invoiceNotes'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('invoiceNumber', $_POST['invoiceNumberl'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('receiptText', $_POST['receiptText'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('receiptNotes', $_POST['receiptNotes'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('hideItemisation', $_POST['hideItemisation'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('reminder1Text', $_POST['reminder1Text'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('reminder2Text', $_POST['reminder2Text'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('reminder3Text', $_POST['reminder3Text'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('budgetCategories', $_POST['budgetCategories'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('expenseApprovalType', $_POST['expenseApprovalType'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('budgetLevelExpenseApproval', $_POST['budgetLevelExpenseApproval'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('expenseRequestTemplate', $_POST['expenseRequestTemplate'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('allowExpenseAdd', $_POST['allowExpenseAdd'], 'Finance' )) $fail = true;
		if (! $this->config->setSettingByScope('reimbursementOfficer', $_POST['reimbursementOfficer'], 'Finance' )) $fail = true;

        if ($fail) {
            $this->insertMessage('return.error.2');
            $this->redirect($URL);
        } else {
            $this->session->getSystemSettings($this->pdo);
            $this->insertMessage('return.success.0', 'success');
            $this->redirect($URL);
        }
    }
}
