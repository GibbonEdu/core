<?php
/**
 * @covers modules/Reports/archive_manage_upload.php
 * @covers modules/Reports/archive_manage_uploadPreview.php
 * @covers modules/Reports/archive_manage_uploadProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('upload a ZIP archive of reports');
$I->loginAsAdmin();

// Test Archive Upload Page (Step 1) ----------------------

$I->amOnModulePage('Reports', 'archive_manage_upload.php');
$I->seeBreadcrumb('Upload Reports');
$I->dontSeeErrors();

// Verify key form elements are present
$I->seeElement('input[name="file"]');
$I->seeElement('select[name="gibbonReportArchiveID"]');
$I->seeElement('select[name="gibbonSchoolYearID"]');
$I->seeElement('input[name="reportIdentifier"]');
$I->seeElement('input[name="reportDate"]');

// Submit Step 1 with ZIP file ----------------------------

$I->attachFile('file', 'test_archive.zip');
$I->selectFromDropdown('gibbonReportArchiveID', 1);
$I->fillField('reportIdentifier', 'TestUploadReport');
$I->fillField('reportDate', date('d/m/Y'));

$I->submitForm('#content form', [], 'Submit');

// Step 2 - Preview page ---------------------------------

$I->seeBreadcrumb('Step 2');
$I->dontSeeErrors();

// Submit Step 2 to process the import
$I->submitForm('#content form', [], 'Submit');

$I->see('Import successful', '.success');

// Cleanup: remove the imported archive entry and file
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonReportArchiveEntryID = $I->grabFromDatabase('gibbonReportArchiveEntry', 'gibbonReportArchiveEntryID', [
    'reportIdentifier' => 'TestUploadReport',
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
]);
$filePath = $I->grabFromDatabase('gibbonReportArchiveEntry', 'filePath', [
    'gibbonReportArchiveEntryID' => $gibbonReportArchiveEntryID,
]);
$I->deleteFromDatabase('gibbonReportArchiveEntry', ['gibbonReportArchiveEntryID' => $gibbonReportArchiveEntryID]);
$I->deleteFile('../uploads/reports/'.$filePath);
