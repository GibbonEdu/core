<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Library Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'librarySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'defaultLoanLength' => '14',
    'browseBGColor'     => 'CECECE',
    'browseBGImage'     => 'test.png',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
