<?php
/**
 * @covers modules/Formal Assessment/internalAssessment_write.php
 * @covers modules/Formal Assessment/internalAssessment_write_data.php
 * @covers modules/Formal Assessment/internalAssessment_write_dataProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Write internal assessments with file upload');
$I->loginAsAdmin();
$I->amOnModulePage('Formal Assessment', 'internalAssessment_write.php');
$I->seeBreadcrumb('Write Internal Assessments');

// Get a course class ID from an existing internal assessment column (ensures valid course with department)
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', ['gibbonCourseID' => $gibbonCourseID]);

// Create an internal assessment column with uploadedResponse enabled
$gibbonInternalAssessmentColumnID = $I->haveInDatabase('gibbonInternalAssessmentColumn', [
    'gibbonCourseClassID'    => $gibbonCourseClassID,
    'name'                   => 'Upload Test Column',
    'description'            => 'Testing file upload for student responses',
    'type'                   => 'Test',
    'attachment'             => '',
    'attainment'             => 'N',
    'gibbonScaleIDAttainment' => null,
    'effort'                 => 'N',
    'gibbonScaleIDEffort'    => null,
    'comment'                => 'Y',
    'uploadedResponse'       => 'Y',
    'completeDate'           => null,
    'complete'               => 'N',
    'viewableStudents'       => 'Y',
    'viewableParents'        => 'Y',
    'gibbonPersonIDCreator'  => '0000000001',
    'gibbonPersonIDLastEdit' => '0000000001',
]);

// Navigate to write data page
$I->amOnModulePage('Formal Assessment', 'internalAssessment_write_data.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
]);

$I->seeBreadcrumb('Enter Internal Assessment Results');

// Fill in the column-level attachment
$I->attachFile('file', 'attachment.txt');

// Fill in student response for the first student (count=1)
// The hidden field 1-gibbonPersonID maps count 1 to the actual student
$I->fillField('comment1', 'Test student comment.');
$I->attachFile('response1', 'attachment.txt');

$I->click('Submit');
$I->seeSuccessMessage();
$I->dontSeeErrors();

// Verify column-level attachment was saved
$columnFile = $I->grabFromDatabase('gibbonInternalAssessmentColumn', 'attachment', [
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
]);
$I->assertNotEmpty($columnFile);

// Grab the first student's person ID from the entry that was created
$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonInternalAssessmentEntry', 'gibbonPersonIDStudent', [
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
    'comment' => 'Test student comment.',
]);

// Verify student response file was saved
$responseFile = $I->grabFromDatabase('gibbonInternalAssessmentEntry', 'response', [
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
]);
$I->assertNotEmpty($responseFile);

// Cleanup: delete all entries for this column, then the column itself
$I->deleteFromDatabase('gibbonInternalAssessmentEntry', [
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
]);
$I->deleteFromDatabase('gibbonInternalAssessmentColumn', [
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID,
]);

if (!empty($columnFile)) {
    $I->deleteFile('../'.$columnFile);
}
if (!empty($responseFile)) {
    $I->deleteFile('../'.$responseFile);
}
