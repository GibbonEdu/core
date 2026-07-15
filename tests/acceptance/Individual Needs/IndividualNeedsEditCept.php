<?php
/**
 * @covers modules/Individual Needs/in_edit.php
 * @covers modules/Individual Needs/in_editProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('edit an individual needs record');
$I->loginAsAdmin();

// Get an existing student with enrolment
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', [
    'status' => 'Full',
    'gibbonRoleIDPrimary' => '003'
]);

$I->amOnModulePage('Individual Needs', 'in_edit.php', [
    'gibbonPersonID' => $gibbonPersonID
]);
$I->seeBreadcrumb('Edit Individual Needs Record');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Edit IEP --------------------------------------------

$formValues = [
    'targets' => '<p>Updated learning targets for the student</p>',
    'strategies' => '<p>Updated teaching strategies to support learning</p>',
    'notes' => '<p>Updated notes and review comments</p>',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Verify changes were saved ---------------------------

$I->amOnModulePage('Individual Needs', 'in_edit.php', [
    'gibbonPersonID' => $gibbonPersonID
]);

$I->see('Updated learning targets for the student');
$I->see('Updated teaching strategies to support learning');
$I->see('Updated notes and review comments');
