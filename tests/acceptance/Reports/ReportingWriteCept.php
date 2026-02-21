<?php
/**
 * @covers modules/Reports/reporting_write.php
 * @covers modules/Reports/reporting_write_byStudent.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reporting write pages');
$I->loginAsAdmin();

// Create test data
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Reporting Cycle',
    'nameShort' => 'Test',
    'sequenceNumber' => 1,
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
]);

$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

$gibbonReportingScopeID = $I->haveInDatabase('gibbonReportingScope', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'scopeType' => 'Year Group',
    'name' => 'Test Scope',
]);

// Reporting Write -------------------------------------------

$I->amOnModulePage('Reports', 'reporting_write.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'scopeTypeID' => $gibbonYearGroupID,
]);
$I->seeBreadcrumb('Write Reports');
$I->dontSeeErrors();

// Reporting Write by Student --------------------------------

$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingstudent']);

$I->amOnModulePage('Reports', 'reporting_write_byStudent.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'scopeTypeID' => $gibbonYearGroupID,
    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
]);
$I->seeBreadcrumb('By Student');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportingScope', ['gibbonReportingScopeID' => $gibbonReportingScopeID]);
$I->deleteFromDatabase('gibbonReportingCycle', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
