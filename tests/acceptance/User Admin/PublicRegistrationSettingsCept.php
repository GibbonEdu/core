<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Public Registration Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'publicRegistrationSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'enablePublicRegistration'           => 'Y',
    'publicRegistrationMinimumAge'       => '64',
    'publicRegistrationDefaultStatus'    => 'Full',
    'publicRegistrationDefaultRole'      => '003',
    'publicRegistrationIntro'            => 'Public Intro Test',
    'publicRegistrationPostscript'       => 'Postscript Test',
    'publicRegistrationPrivacyStatement' => 'Privacy Test',
    'publicRegistrationAgreement'        => 'Agreement Test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
