<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Student Settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'studentsSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'enableStudentNotes'            => 'Y',
    'noteCreationNotification'      => 'Tutors',
    'studentAgreementOptions'       => 'Option1,Option2,Option3',
    'dayTypeOptions'                => 'Day,Type,Option,Test',
    'dayTypeText'                   => 'Day-Type Test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
