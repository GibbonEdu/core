<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Individual Needs Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'inSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'targetsTemplate'            => '<div>Targets Test</div>',
    'teachingStrategiesTemplate' => '<span>Strategies Test</span>',
    'notesReviewTemplate'        => '<p>Notes Test</p>',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
