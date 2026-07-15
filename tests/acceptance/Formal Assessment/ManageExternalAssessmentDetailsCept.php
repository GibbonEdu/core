<?php
/**
 * @covers modules/Formal Assessment/externalAssessment_manage_details_add.php
 * @covers modules/Formal Assessment/externalAssessment_manage_details_edit.php
 * @covers modules/Formal Assessment/externalAssessment_manage_details_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage external assessment details');
$I->loginAsAdmin();

// Get a student ID from the database
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

// Create test data directly in database
$gibbonExternalAssessmentID = $I->grabFromDatabase('gibbonExternalAssessment', 'gibbonExternalAssessmentID', ['active' => 'Y']);

// Enable file upload for this assessment type
$I->updateInDatabase('gibbonExternalAssessment', ['allowFileUpload' => 'Y'], ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID]);

$gibbonExternalAssessmentStudentID = $I->haveInDatabase('gibbonExternalAssessmentStudent', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
    'date' => '2024-01-15',
    'attachment' => '',
]);

// Edit ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_edit.php', [
    'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
    'gibbonPersonID' => $gibbonPersonID
]);
$I->seeBreadcrumb('Edit Assessment');

$I->seeInField('date', '2024-01-15');

$I->attachFile('file', 'attachment.txt');

$formValues = [
    'date' => '2024-02-20',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$file = $I->grabFromDatabase('gibbonExternalAssessmentStudent', 'attachment', ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID]);
$I->assertNotEmpty($file);

// Edit again to remove attachment ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_edit.php', [
    'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
    'gibbonPersonID' => $gibbonPersonID
]);

$I->fillField('attachment', '');
$I->submitForm('#content form', ['date' => '2024-02-20'], 'Submit');
$I->seeSuccessMessage();

$I->seeInDatabase('gibbonExternalAssessmentStudent', ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID, 'attachment' => '']);

// Add - File Upload ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_add.php', [
    'gibbonExternalAssessmentID' => $gibbonExternalAssessmentID,
    'gibbonPersonID' => $gibbonPersonID,
    'step' => 2,
]);
$I->seeBreadcrumb('Add Assessment');

$I->attachFile('file', 'attachment2.png');
$I->submitForm('#content form', ['date' => '2024-03-15'], 'Submit');
$I->seeSuccessMessage();

$gibbonExternalAssessmentStudentID2 = $I->grabEditIDFromURL();
$file2 = $I->grabFromDatabase('gibbonExternalAssessmentStudent', 'attachment', ['gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID2]);
$I->assertNotEmpty($file2);

// Delete second record ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_delete.php', [
    'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID2,
    'gibbonPersonID' => $gibbonPersonID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_delete.php', [
    'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
    'gibbonPersonID' => $gibbonPersonID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Cleanup ------------------------------------------------
$I->updateInDatabase('gibbonExternalAssessment', ['allowFileUpload' => 'N'], ['gibbonExternalAssessmentID' => $gibbonExternalAssessmentID]);
