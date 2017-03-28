<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Manage Staff Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'staffSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'salaryScalePositions'          => '0,1,1,2,3,5,8,13,21,34',
    'responsibilityPosts'           => 'Post 1,Post 2,Post 3',
    'jobOpeningDescriptionTemplate' => '<div>Job Template Test</div>',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
