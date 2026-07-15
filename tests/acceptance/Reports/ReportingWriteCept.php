<?php
/**
 * @covers modules/Reports/reporting_write.php
 * @covers modules/Reports/reporting_writeProcess.php
 * @covers modules/Reports/reporting_write_byStudent.php
 * @covers modules/Reports/reporting_write_byStudentProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reporting write with image file upload');
$I->loginAsAdmin();

// Setup: get current school year and a year group with enrolled students
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonYearGroupID = $I->grabFromDatabase('gibbonStudentEnrolment', 'gibbonYearGroupID', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
]);

// Get a student enrolled in this year group
$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonStudentEnrolment', 'gibbonPersonID', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonYearGroupID'  => $gibbonYearGroupID,
]);

// Create an Image criteria type
$gibbonReportingCriteriaTypeID = $I->haveInDatabase('gibbonReportingCriteriaType', [
    'name'      => 'Test Image Upload',
    'valueType' => 'Image',
    'active'    => 'Y',
]);

// Create a reporting cycle spanning today
$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID'  => $gibbonSchoolYearID,
    'name'                => 'Test Upload Cycle',
    'nameShort'           => 'TUC',
    'sequenceNumber'      => 99,
    'dateStart'           => date('Y-m-d', strtotime('-1 day')),
    'dateEnd'             => date('Y-m-d', strtotime('+30 days')),
    'gibbonYearGroupIDList' => $gibbonYearGroupID,
]);

// Create a reporting scope (Year Group)
$gibbonReportingScopeID = $I->haveInDatabase('gibbonReportingScope', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'scopeType'              => 'Year Group',
    'name'                   => 'Test Upload Scope',
]);

// Create reporting access (role-based for admin role 001)
$gibbonReportingAccessID = $I->haveInDatabase('gibbonReportingAccess', [
    'gibbonReportingCycleID'   => $gibbonReportingCycleID,
    'gibbonReportingScopeIDList' => $gibbonReportingScopeID,
    'gibbonRoleIDList'         => '001',
    'accessType'               => 'Role',
    'dateStart'                => date('Y-m-d', strtotime('-1 day')),
    'dateEnd'                  => date('Y-m-d', strtotime('+30 days')),
    'canWrite'                 => 'Y',
    'canProofRead'             => 'N',
]);

// Create a Per Group criteria (for reporting_write.php)
$gibbonReportingCriteriaID_group = $I->haveInDatabase('gibbonReportingCriteria', [
    'gibbonReportingCycleID'       => $gibbonReportingCycleID,
    'gibbonReportingScopeID'       => $gibbonReportingScopeID,
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID,
    'gibbonYearGroupID'            => $gibbonYearGroupID,
    'target'                       => 'Per Group',
    'name'                         => 'Group Image Upload',
    'sequenceNumber'               => 1,
]);

// Create a Per Student criteria (for reporting_write_byStudent.php)
$gibbonReportingCriteriaID_student = $I->haveInDatabase('gibbonReportingCriteria', [
    'gibbonReportingCycleID'       => $gibbonReportingCycleID,
    'gibbonReportingScopeID'       => $gibbonReportingScopeID,
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID,
    'gibbonYearGroupID'            => $gibbonYearGroupID,
    'target'                       => 'Per Student',
    'name'                         => 'Student Image Upload',
    'sequenceNumber'               => 1,
]);

// Test 1: Per Group — reporting_write.php --------------------------------
$I->amOnModulePage('Reports', 'reporting_write.php', [
    'gibbonSchoolYearID'    => $gibbonSchoolYearID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'scopeTypeID'           => $gibbonYearGroupID,
]);
$I->seeBreadcrumb('Write Reports');

// Criteria IDs are zero-padded to 12 digits in the form field names
$groupCriteriaField = 'file'.str_pad($gibbonReportingCriteriaID_group, 12, '0', STR_PAD_LEFT);
$studentCriteriaField = 'file'.str_pad($gibbonReportingCriteriaID_student, 12, '0', STR_PAD_LEFT);

$I->attachFile($groupCriteriaField, 'attachment.jpg');
$I->click('Submit');
$I->seeSuccessMessage();

// Verify the value was saved
$groupFile = $I->grabFromDatabase('gibbonReportingValue', 'value', [
    'gibbonReportingCriteriaID' => $gibbonReportingCriteriaID_group,
]);
$I->assertNotEmpty($groupFile);

// Test 2: Per Student — reporting_write_byStudent.php --------------------------------
$I->amOnModulePage('Reports', 'reporting_write_byStudent.php', [
    'gibbonSchoolYearID'     => $gibbonSchoolYearID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'scopeTypeID'            => $gibbonYearGroupID,
    'gibbonPersonIDStudent'  => $gibbonPersonIDStudent,
]);
$I->seeBreadcrumb('By Student');

$I->attachFile($studentCriteriaField, 'attachment.jpg');
$I->click('Save');
$I->seeSuccessMessage();

// Verify the value was saved
$studentFile = $I->grabFromDatabase('gibbonReportingValue', 'value', [
    'gibbonReportingCriteriaID' => $gibbonReportingCriteriaID_student,
    'gibbonPersonIDStudent'     => $gibbonPersonIDStudent,
]);
$I->assertNotEmpty($studentFile);

// Cleanup --------------------------------
$I->deleteFromDatabase('gibbonReportingValue', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
]);
$I->deleteFromDatabase('gibbonReportingProgress', [
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
]);
$I->deleteFromDatabase('gibbonReportingCriteria', [
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
]);
$I->deleteFromDatabase('gibbonReportingAccess', [
    'gibbonReportingAccessID' => $gibbonReportingAccessID,
]);
$I->deleteFromDatabase('gibbonReportingScope', [
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
]);
$I->deleteFromDatabase('gibbonReportingCycle', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
]);
$I->deleteFromDatabase('gibbonReportingCriteriaType', [
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID,
]);

if (!empty($groupFile)) {
    $I->deleteFile('../'.$groupFile);
}
if (!empty($studentFile)) {
    $I->deleteFile('../'.$studentFile);
}
