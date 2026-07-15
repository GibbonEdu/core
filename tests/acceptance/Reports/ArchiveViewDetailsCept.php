<?php
/**
 * @covers modules/Reports/archive_byReport_view.php
 * @covers modules/Reports/archive_byStudent_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view archive details by report and by student');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', [
    'status' => 'Current'
]);

// Get an existing student
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', [
    'status' => 'Full'
]);

// Get the default archive
$gibbonReportArchiveID = $I->grabFromDatabase('gibbonReportArchive', 'gibbonReportArchiveID', []);

// Create a test report
$gibbonReportID = $I->haveInDatabase('gibbonReport', [
    'gibbonReportArchiveID' => $gibbonReportArchiveID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Report Archive View',
    'active' => 'Y',
    'status' => 'Published',
]);

// Create a test archive entry
$gibbonReportArchiveEntryID = $I->haveInDatabase('gibbonReportArchiveEntry', [
    'gibbonReportArchiveID' => $gibbonReportArchiveID,
    'gibbonReportID' => $gibbonReportID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonPersonID' => $gibbonPersonID,
    'type' => 'Single',
    'status' => 'Final',
    'reportIdentifier' => 'test-report-archive',
    'filePath' => '/test/path.pdf',
    'timestampCreated' => date('Y-m-d H:i:s'),
]);

// Test Archive by Report ---------------------------------

$I->amOnModulePage('Reports', 'archive_byReport.php');
$I->seeBreadcrumb('View by Report');

// Basic Check
$I->dontSeeErrors();

// Test Archive by Report View -----------------------------

$I->amOnModulePage('Reports', 'archive_byReport_view.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonReportID' => $gibbonReportID
]);
$I->seeBreadcrumb('View by Report');
$I->dontSeeErrors();

// Test filter functionality
$I->selectFromDropdown('gibbonReportID', 1);
$I->submitForm('#content form', []);
$I->dontSeeErrors();

// Test Archive by Student ---------------------------------

$I->amOnModulePage('Reports', 'archive_byStudent.php');
$I->seeBreadcrumb('View by Student');

// Basic Check
$I->dontSeeErrors();

// Test Archive by Student View ---------------------------

$I->amOnModulePage('Reports', 'archive_byStudent_view.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'allStudents' => 'on',
]);
$I->seeBreadcrumb('View by Student');
$I->dontSeeErrors();

// Clean up test data --------------------------------------

$I->deleteFromDatabase('gibbonReportArchiveEntry', ['gibbonReportArchiveEntryID' => $gibbonReportArchiveEntryID]);
$I->deleteFromDatabase('gibbonReport', ['gibbonReportID' => $gibbonReportID]);
