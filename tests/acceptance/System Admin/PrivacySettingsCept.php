<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('update Security & Privacy Settings');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'privacySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'passwordPolicyMinLength'       => '7',
    'passwordPolicyAlpha'           => 'Y',
    'passwordPolicyNumeric'         => 'Y',
    'passwordPolicyNonAlphaNumeric' => 'Y',
    'sessionDuration'               => '2048',
    'cookieConsentEnabled'          => 'Y',
    'cookieConsentText'             => 'Test',
    'privacyPolicy'                 => 'Testing',
);


$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
