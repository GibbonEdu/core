<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('update Planner Settings');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'plannerSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'lessonDetailsTemplate'                    => '<div>Lesson Test</div>',
    'teachersNotesTemplate'                    => '<span>Notes Test</span>',
    'unitOutlineTemplate'                      => '<p>Unit Test</p>',
    'smartBlockTemplate'                       => '<b>Block Test</b>',
    'makeUnitsPublic'                          => 'Y',
    'shareUnitOutline'                         => 'Y',
    'allowOutcomeEditing'                      => 'Y',
    'sharingDefaultParents'                    => 'Y',
    'sharingDefaultStudents'                   => 'Y',
    'parentWeeklyEmailSummaryIncludeBehaviour' => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
