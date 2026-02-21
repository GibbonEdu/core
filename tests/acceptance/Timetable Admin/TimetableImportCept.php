<?php
/**
 * @covers modules/Timetable Admin/tt_import.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check timetable import page');
$I->loginAsAdmin();

// Get an existing timetable to test import
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonTTID = $I->grabFromDatabase('gibbonTT', 'gibbonTTID', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);

$I->amOnModulePage('Timetable Admin', 'tt_import.php', [
    'gibbonTTID' => $gibbonTTID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Import Timetable Data');

// Basic Check - Step 1 (CSV Upload Form) ---------------

$I->dontSeeErrors();
$I->see('Step 1 - Select CSV Files');
$I->see('CSV File');
$I->see('Field Delimiter');
$I->see('String Enclosure');

// Note: We cannot test the actual import workflow without a valid CSV file
// and existing timetable structure. This test verifies the import page loads
// correctly and displays the upload form.
