<?php
/**
 * @covers modules/Finance/invoices_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage invoices');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'invoices_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Manage Invoices');

// Filter Test -----------------------------------------

$I->selectFromDropdown('status', 1);
$I->submitForm('#manageInvoices', []);
$I->dontSeeErrors();

// TODO: Add full CRUD test for invoices
// Requires: billing schedules, fee categories, and invoicees to exist
