<?php
/**
 * @covers modules/School Admin/trackingSettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update tracking settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'trackingSettings.php');
$I->seeBreadcrumb('Tracking Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Submit the form without modifications (tracking settings are complex checkboxes with hidden fields)
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('School Admin', 'trackingSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
