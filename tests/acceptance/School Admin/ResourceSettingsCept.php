<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Resource Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'resourceSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'categories'         => 'Category1,Category2,Category3,Category4,Category5',
    'purposesGeneral'    => 'General1,General2,General3',
    'purposesRestricted' => 'Restricted1,Restricted2,Restricted3,Restricted4',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
