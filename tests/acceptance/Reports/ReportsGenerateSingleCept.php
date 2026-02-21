<?php
/**
 * @covers modules/Reports/reports_generate_single.php
 * @covers modules/Reports/reports_generate_singleDebug.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reports generate single workflow');
$I->loginAsAdmin();

// Create test data
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Create a report template
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Template',
    'context' => 'Student Enrolment',
]);

// Create a report
$gibbonReportID = $I->haveInDatabase('gibbonReport', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
    'name' => 'Test Report',
]);

$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

// Reports Generate Single -----------------------------------

$I->amOnModulePage('Reports', 'reports_generate_single.php', [
    'gibbonReportID' => $gibbonReportID,
    'contextData' => $gibbonYearGroupID,
]);
$I->seeBreadcrumb('Single');
$I->dontSeeErrors();

// Reports Generate Single Debug -----------------------------

$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->amOnModulePage('Reports', 'reports_generate_singleDebug.php', [
    'gibbonReportID' => $gibbonReportID,
    'contextData' => $gibbonYearGroupID,
    'gibbonPersonIDStudent' => $gibbonPersonIDStudent,
]);
$I->seeBreadcrumb('Debug');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReport', ['gibbonReportID' => $gibbonReportID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
