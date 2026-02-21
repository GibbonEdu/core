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

$formValues = [
    'date' => '2024-02-20',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'externalAssessment_manage_details_delete.php', [
    'gibbonExternalAssessmentStudentID' => $gibbonExternalAssessmentStudentID,
    'gibbonPersonID' => $gibbonPersonID
]);

$I->click('Delete');
$I->seeSuccessMessage();
