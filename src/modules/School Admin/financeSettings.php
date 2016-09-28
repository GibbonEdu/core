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

use Gibbon\core\view ;
use Gibbon\People\staff ;

if (! $this instanceof view) die();

if ($this->getSecurity()->isActionAccessible()) {
	//Proceed!
	$trail = $this->initiateTrail();
	$trail->trailEnd = 'Manage Finance Settings';
	$trail->render($this);
	
	$this->render('default.flash');

	$this->h2('Manage Finance Settings');
	$form = $this->getForm(GIBBON_ROOT . "modules/School Admin/financeSettingsProcess.php", array(), true);
	
	$form->addElement('h3', null, 'General Settings');

	$el = $form->addElement('email', null);
	$el->injectRecord($this->config->getSetting('email', 'Finance'));
	$el->setRequired();

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('financeOnlinePaymentEnabled', 'Finance'));

	$el = $form->addElement('number', null);
	$el->injectRecord($this->config->getSetting('financeOnlinePaymentThreshold', 'Finance'));
	$el->setMaxLength(25);
	$el->validate->Numericality = true ;
	$el->description .= ' In ' . $this->config->getSettingByScope('System', 'currency');

	$form->addElement('h3', null, 'Invoices');

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('invoiceText', 'Finance'));
	$el->rows = 4;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('invoiceNotes', 'Finance'));
	$el->rows = 4;

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('invoiceNumber', 'Finance'));
	$el->validateOff();
	$el->addOption($this->__('Invoice ID'), 'Invoice ID');
	$el->addOption($this->__('Person ID + Invoice ID'), 'Person ID + Invoice ID');
	$el->addOption($this->__('Student ID + Invoice ID'), 'Student ID + Invoice ID');

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('receiptText', 'Finance'));
	$el->rows = 4;

	$form->addElement('h3', null, 'Receipts');

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('receiptText', 'Finance'));
	$el->validateOff();
	$el->rows = 4;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('receiptNotes', 'Finance'));
	$el->rows = 4;

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('hideItemisation', 'Finance'));

	$form->addElement('h3', null, 'Reminders');

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('reminder1Text', 'Finance'));
	$el->validateOff();
	$el->rows = 4;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('reminder2Text', 'Finance'));
	$el->validateOff();
	$el->rows = 4;

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('reminder3Text', 'Finance'));
	$el->validateOff();
	$el->rows = 4;

	$form->addElement('h3', null, 'Expenses');

	$el = $form->addElement('text', null);
	$el->injectRecord($this->config->getSetting('budgetCategories', 'Finance'));
	$el->setRequired();
	$el->setMaxLength(255); 

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('expenseApprovalType', 'Finance'));
	$el->validateOff();
	$el->addOption($this->__('One Of'), 'One Of');
	$el->addOption($this->__('Two Of'), 'Two Of');
	$el->addOption($this->__('Chain Of All'), 'Chain Of All');

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('budgetLevelExpenseApproval', 'Finance'));

	$el = $form->addElement('textArea', null);
	$el->injectRecord($this->config->getSetting('expenseRequestTemplate', 'Finance'));
	$el->validateOff();
	$el->rows = 4;

	$el = $form->addElement('yesno', null);
	$el->injectRecord($this->config->getSetting('allowExpenseAdd', 'Finance'));

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('purchasingOfficer', 'Finance'));
	$el->validateOff();
	$pObj = new staff($this);
	$people = $pObj->findAllStaffByType();
	$el->addOption('');
	foreach ($people as $person)
		$el->addOption($this->htmlPrep($person->formatName(true, true)), $person->getField('gibbonPersonID'));

	$el = $form->addElement('select', null);
	$el->injectRecord($this->config->getSetting('reimbursementOfficer', 'Finance'));
	$el->validateOff();
	$el->addOption('');
	foreach ($people as $person)
		$el->addOption($this->htmlPrep($person->formatName(true, true)), $person->getField('gibbonPersonID'));

	$form->addElement('submitBtn', null);	
	$form->render();
}
