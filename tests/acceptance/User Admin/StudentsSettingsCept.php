<?php 
/**
 * @covers modules/User Admin/studentsSettings.php
 * @covers modules/User Admin/studentsSettings_noteCategory_add.php
 * @covers modules/User Admin/studentsSettings_noteCategory_edit.php
 * @covers modules/User Admin/studentsSettings_noteCategory_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage student settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'studentsSettings.php');

// Add Note Category -------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Note Category');

$addFormValues = [
    'name' => 'Test Note Category',
    'active' => 'Y',
    'template' => '<p>Test template content</p>',
];

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonStudentNoteCategoryID = $I->grabEditIDFromURL();

// Edit Note Category ------------------------------------
$I->amOnModulePage('User Admin', 'studentsSettings_noteCategory_edit.php', [
    'gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID
]);
$I->seeBreadcrumb('Edit Note Category');

$I->seeInFormFields('#content form', [
    'name' => 'Test Note Category',
]);

$editFormValues = [
    'name' => 'Updated Note Category',
    'active' => 'N',
    'template' => '<p>Updated template content</p>',
];

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Note Category ----------------------------------
$I->amOnModulePage('User Admin', 'studentsSettings_noteCategory_delete.php', [
    'gibbonStudentNoteCategoryID' => $gibbonStudentNoteCategoryID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Test Settings Page (original test) -------------------
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
