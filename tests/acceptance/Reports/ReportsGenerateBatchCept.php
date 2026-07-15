<?php
/**
 * @covers modules/Reports/reports_generate_batch.php
 * @covers modules/Reports/reports_generate_batchConfirm.php
 * @covers modules/Reports/reports_generate_cancelConfirm.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reports generate batch workflow');
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

// Reports Generate Batch ------------------------------------

$I->amOnModulePage('Reports', 'reports_generate_batch.php', [
    'gibbonReportID' => $gibbonReportID,
]);
$I->seeBreadcrumb('Run');
$I->dontSeeErrors();

// Reports Generate Batch Confirm ----------------------------

$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

$I->amOnModulePage('Reports', 'reports_generate_batchConfirm.php', [
    'gibbonReportID' => $gibbonReportID,
    'contextData' => $gibbonYearGroupID,
]);
$I->dontSeeErrors();

// Reports Generate Cancel Confirm ---------------------------

$I->amOnModulePage('Reports', 'reports_generate_cancelConfirm.php', [
    'gibbonReportID' => $gibbonReportID,
    'processID' => 1,
]);
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReport', ['gibbonReportID' => $gibbonReportID]);
$I->deleteFromDatabase('gibbonReportTemplate', ['gibbonReportTemplateID' => $gibbonReportTemplateID]);
