<?php
/**
 * @covers modules/School Admin/admissions_settings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update admissions settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'admissions_settings.php');
$I->seeBreadcrumb('Admissions Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values (use array_replace to modify only specific fields)
$formValues = array_replace($originalFormValues, array(
    'publicApplications' => 'Y',
    'admissionsEnabled' => 'Y',
    'admissionsLinkName' => 'Test Admissions Link',
));

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('School Admin', 'admissions_settings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
