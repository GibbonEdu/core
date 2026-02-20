<?php
/**
 * @covers modules/User Admin/personalDocumentSettings.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update personal document settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'personalDocumentSettings.php');
$I->seeBreadcrumb('Personal Document Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values (use array_replace to modify only specific fields)
$formValues = array_replace($originalFormValues, array(
    'residencyStatus' => 'Citizen, Permanent Resident, Visa Holder',
));

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('User Admin', 'personalDocumentSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
