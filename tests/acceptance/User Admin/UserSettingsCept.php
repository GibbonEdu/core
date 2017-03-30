<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update User Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'userSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'nationality'        => 'Nationality 1,Nationality 2,Nationality 3',
    'ethnicity'          => 'Ethnicity 1,Ethnicity 2',
    'religions'          => 'Religion 1,Religion 3,Religion 3,Religion 4',
    'residencyStatus'    => 'Status Test',
    'departureReasons'   => 'Reason 1,Reason 2,Reason 3',
    'privacy'            => 'Y',
    'privacyBlurb'       => 'Privacy Blurb Test',
    'privacyOptions'     => 'Privacy 1,Privacy 2,Privacy 3',
    'personalBackground' => 'Y',
    'dayTypeOptions'     => 'Day,Type,Option,Test',
    'dayTypeText'        => 'Day-Type Test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
