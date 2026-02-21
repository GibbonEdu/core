<?php
/**
 * @covers modules/Reports/reports_send_batch.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reports send batch page');
$I->loginAsAdmin();

// Create test data
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Create a report archive
$gibbonReportArchiveID = $I->haveInDatabase('gibbonReportArchive', [
    'name' => 'Test Archive',
    'viewableParents' => 'Y',
    'viewableStudents' => 'Y',
]);

// Create a report template
$gibbonReportTemplateID = $I->haveInDatabase('gibbonReportTemplate', [
    'name' => 'Test Template',
    'context' => 'Student Enrolment',
]);

// Create a report
$gibbonReportID = $I->haveInDatabase('gibbonReport', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonReportTemplateID' => $gibbonReportTemplateID,
    'gibbonReportArchiveID' => $gibbonReportArchiveID,
    'name' => 'Test Report',
]);

$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

// Reports Send Batch ----------------------------------------

$I->amOnModulePage('Reports', 'reports_send_batch.php', [
    'gibbonReportID' => $gibbonReportID,
    'contextData' => $gibbonYearGroupID,
]);
$I->seeBreadcrumb('Select Reports');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReport', ['gibbonReportID' => $gibbonReportID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
$I->deleteFromDatabase('gibbonReportArchive', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
