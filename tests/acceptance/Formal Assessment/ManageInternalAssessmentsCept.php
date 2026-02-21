<?php
/**
 * @covers modules/Formal Assessment/internalAssessment_manage.php
 * @covers modules/Formal Assessment/internalAssessment_manage_add.php
 * @covers modules/Formal Assessment/internalAssessment_manage_edit.php
 * @covers modules/Formal Assessment/internalAssessment_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage internal assessment columns');
$I->loginAsAdmin();

// Get a course class ID
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);

$I->amOnModulePage('Formal Assessment', 'internalAssessment_manage.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);
$I->seeBreadcrumb('Manage');

// Create test data directly in database
$gibbonInternalAssessmentColumnID = $I->haveInDatabase('gibbonInternalAssessmentColumn', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'name' => 'Test Assessment',
    'description' => 'Test Description',
    'type' => 'Test',
    'attachment' => '',
    'attainment' => 'N',
    'gibbonScaleIDAttainment' => null,
    'effort' => 'N',
    'gibbonScaleIDEffort' => null,
    'comment' => 'N',
    'uploadedResponse' => 'N',
    'completeDate' => '2024-06-30',
    'complete' => 'N',
    'viewableStudents' => 'Y',
    'viewableParents' => 'Y',
    'gibbonPersonIDCreator' => '0000000001',
    'gibbonPersonIDLastEdit' => '0000000001',
]);

// Edit ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'internalAssessment_manage_edit.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID
]);
$I->seeBreadcrumb('Edit Column');

$I->seeInField('name', 'Test Assessment');

// Select type if available
$I->selectFromDropdown('type', 1);

$formValues = [
    'name' => 'Updated Assessment',
    'description' => 'Updated Description',
    'attainment' => 'N',
    'effort' => 'N',
    'comment' => 'N',
    'uploadedResponse' => 'N',
    'viewableStudents' => 'Y',
    'viewableParents' => 'Y',
    'completeDate' => '2024-07-15',
    'complete' => 'Y',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Formal Assessment', 'internalAssessment_manage_delete.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID
]);

$I->click('Delete');
$I->seeSuccessMessage();
