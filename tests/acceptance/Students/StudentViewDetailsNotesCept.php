<?php
/**
 * @covers modules/Students/student_view_details_notes_add.php
 * @covers modules/Students/student_view_details_notes_edit.php
 * @covers modules/Students/student_view_details_notes_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage student notes');
$I->loginAsAdmin();

$gibbonActionID = $I->grabFromDatabase('gibbonAction', 'gibbonActionID', ['name' => 'View Student Profile_fullEditAllNotes']);
$gibbonPermissionID = $I->haveInDatabase('gibbonPermission', ['gibbonRoleID' => 1, 'gibbonActionID' => $gibbonActionID]);

// Get a student to work with
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['gibbonRoleIDPrimary' => '003', 'status' => 'Full']);

// Add Student Note ------------------------------------

$I->amOnModulePage('Students', 'student_view_details_notes_add.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'subpage' => 'Notes'
]);
$I->seeBreadcrumb('Add Student Note');

$I->selectFromDropdown('gibbonStudentNoteCategoryID', 1);

$formValues = array(
    'title' => 'Test Note Title',
    'note' => 'Test note content',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonStudentNoteID = $I->grabEditIDFromURL();

// Edit Student Note -----------------------------------

$I->amOnModulePage('Students', 'student_view_details_notes_edit.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'subpage' => 'Notes',
    'gibbonStudentNoteID' => $gibbonStudentNoteID
]);
$I->seeBreadcrumb('Edit Student Note');

$I->seeInFormFields('#content form', array(
    'title' => 'Test Note Title',
));

$formValues = array(
    'title' => 'Updated Note Title',
    'note' => 'Updated note content',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete Student Note ---------------------------------

$I->amOnModulePage('Students', 'student_view_details_notes_delete.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'subpage' => 'Notes',
    'gibbonStudentNoteID' => $gibbonStudentNoteID
]);

$I->click('Delete');
$I->seeSuccessMessage();


$I->deleteFromDatabase('gibbonPermission', ['permissionID' => $gibbonPermissionID]);
