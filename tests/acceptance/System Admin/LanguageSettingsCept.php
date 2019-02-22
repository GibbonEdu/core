<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Language Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'i18n_manage.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$I->selectOption('gibboni18nID', '0002');
$I->submitForm('#content form', array(), 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeOptionIsSelected('gibboni18nID', '0002');

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
