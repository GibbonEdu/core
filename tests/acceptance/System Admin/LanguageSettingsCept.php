<?php 
/**
 * @covers modules/System Admin/i18n_manage.php
 * @covers modules/System Admin/i18n_manage_install.php
 * @covers modules/System Admin/i18n_manage_updateAll.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update Language Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'i18n_manage.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$I->selectOption('gibboni18nID', '0001');
$I->submitForm('#content form', array(), 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeOptionIsSelected('gibboni18nID', '0001');

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

// Test Install Page (DataTable action) -----------------------

$gibboni18nID = $I->grabFromDatabase('gibboni18n', 'gibboni18nID', []);

$I->amOnModulePage('System Admin', 'i18n_manage_install.php', [
    'gibboni18nID' => $gibboni18nID
]);
$I->dontSeeErrors();

// Test Update All Page (DataTable action) --------------------

$I->amOnModulePage('System Admin', 'i18n_manage_updateAll.php');
$I->dontSeeErrors();
