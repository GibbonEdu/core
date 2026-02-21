<?php
/**
 * @covers modules/Finance/invoices_manage.php
 * @covers modules/Finance/invoices_manage_add.php
 * @covers modules/Finance/invoices_manage_edit.php
 * @covers modules/Finance/invoices_manage_delete.php
 * @covers modules/Finance/invoices_manage_issue.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit, issue and delete invoices');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'invoices_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Manage Invoices');

// Filter Test -----------------------------------------

$I->selectFromDropdown('status', 1);
$I->submitForm('#manageInvoices', []);
$I->dontSeeErrors();

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Fees');

// Select an invoicee
$I->selectFromDropdown('gibbonFinanceInvoiceeIDs', 1);

// Select scheduling type
$I->click('input[name="scheduling"][value="Ad Hoc"]');

// Set invoice due date
$invoiceDueDate = date('d/m/Y', strtotime('+30 days'));
$I->fillField('invoiceDueDate', $invoiceDueDate);

// Add notes
$I->fillField('notes', 'Test invoice notes');

$I->submitForm('#content form', ['scheduling' => 'Ad Hoc', 'name0' => 'Test Fee', 'fee0' => '100.00', 'gibbonFinanceFeeCategoryID0' => 1, 'gibbonFinanceFeeID0' => 0, 'feeType0' => 'Ad Hoc', 'order' => [0 => 0]], 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceInvoiceID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'invoices_manage_edit.php', [
    'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Edit Invoice');

$I->seeInField('notes', 'Test invoice notes');

// Update notes
$I->fillField('notes', 'Updated invoice notes');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Issue ------------------------------------------------
$I->amOnModulePage('Finance', 'invoices_manage_issue.php', [
    'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Issue Invoice');

$I->submitForm('#content form', [], 'Submit');
$I->see('Your request was completed successfully', '.success');

// Delete Data ------------------------------------------------
// (Note: Can only delete pending invoices, so create a new one)
$I->amOnModulePage('Finance', 'invoices_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->clickNavigation('Add');

$I->selectFromDropdown('gibbonFinanceInvoiceeIDs', 1);
$I->fillField('invoiceDueDate', $invoiceDueDate);

$I->submitForm('#content form', ['scheduling' => 'Ad Hoc', 'name0' => 'Test Fee', 'fee0' => '100.00', 'gibbonFinanceFeeCategoryID0' => 1, 'gibbonFinanceFeeID0' => 0, 'feeType0' => 'Ad Hoc', 'order' => [0 => 0]], 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceInvoiceIDToDelete = $I->grabEditIDFromURL();

// Delete ------------------------------------------------
$I->amOnModulePage('Finance', 'invoices_manage_delete.php', [
    'gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceIDToDelete,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);

$I->click('Delete');
$I->seeSuccessMessage();

$I->deleteFromDatabase('gibbonFinanceInvoice', ['gibbonFinanceInvoiceID' => $gibbonFinanceInvoiceID]);
