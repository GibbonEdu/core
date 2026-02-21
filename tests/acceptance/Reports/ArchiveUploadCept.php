<?php
/**
 * @covers modules/Reports/archive_manage_upload.php
 * @covers modules/Reports/archive_manage_uploadPreview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check archive upload workflow');
$I->loginAsAdmin();

// Test Archive Upload Page (Step 1) ----------------------

$I->amOnModulePage('Reports', 'archive_manage_upload.php');
$I->seeBreadcrumb('Upload Reports');
$I->dontSeeErrors();

// Verify key form elements are present
$I->seeElement('input[name="file"]');
$I->seeElement('input[name="useSeparator"]');
$I->seeElement('select[name="gibbonReportArchiveID"]');
$I->seeElement('select[name="gibbonSchoolYearID"]');
$I->seeElement('input[name="reportIdentifier"]');
$I->seeElement('input[name="reportDate"]');

