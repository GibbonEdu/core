<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('submit and approve a family data update');
$I->loginAsAdmin();

// Setup Invoiceees --------------------------------------
$I->amOnModulePage('Finance', 'invoicees_manage.php');

// Select ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_finance.php');
$I->seeBreadcrumb('Update Finance Data');

$I->selectFromDropdown('gibbonFinanceInvoiceeID', 2);
$I->click('Submit');

// Simple Update ------------------------------------------
$I->see('Update Data');

$I->selectOption('invoiceTo', 'Family');

$I->click('#content form[method="post"] input[type=submit]');
$I->seeSuccessMessage();

// Complex Update ------------------------------------------
$I->selectOption('invoiceTo', 'Company');

$editFormValues = array(
    'companyName'     => 'McTest Ltd.',
    'companyContact'  => 'Testing McTest',
    'companyAddress'  => '123 Ficticious Lane',
    'companyEmail'    => 'test@testing.local',
    'companyCCFamily' => 'Y',
    'companyPhone'    => '12345678',
    'companyAll'      => 'Y',
);

$I->submitForm('#content form[method="post"]', $editFormValues, 'Submit');

// Confirm ------------------------------------------------
$I->seeSuccessMessage();
$I->seeInFormFields('#content form[method="post"]', $editFormValues);

$gibbonFinanceInvoiceeUpdateID = $I->grabValueFrom("input[type='hidden'][name='existing']");

// Accept ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_finance_manage_edit.php', array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID));
$I->seeBreadcrumb('Edit Request');

$I->see('McTest Ltd.', 'td');
$I->see('Testing McTest', 'td');
$I->see('123 Ficticious Lane', 'td');
$I->see('test@testing.local', 'td');
$I->see('Y', 'td');
$I->see('12345678', 'td');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonFinanceInvoiceeUpdateID = $I->grabValueFromURL('gibbonFinanceInvoiceeUpdateID');

// Delete ------------------------------------------------
$I->amOnModulePage('Data Updater', 'data_finance_manage_delete.php', array('gibbonFinanceInvoiceeUpdateID' => $gibbonFinanceInvoiceeUpdateID));
$I->seeBreadcrumb('Delete Request');

$I->click('Yes');
$I->seeSuccessMessage();