<?php
/**
 * @covers modules/Timetable Admin/courseEnrolment_sync.php
 * @covers modules/Timetable Admin/courseEnrolment_sync_add.php
 * @covers modules/Timetable Admin/courseEnrolment_sync_edit.php
 * @covers modules/Timetable Admin/courseEnrolment_sync_run.php
 * @covers modules/Timetable Admin/courseEnrolment_sync_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage sync course enrolment');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'courseEnrolment_sync.php');
$I->seeBreadcrumb('Sync Course Enrolment');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Add a sync mapping first ----------------------------

$I->click('Add');
$I->seeInCurrentUrl('courseEnrolment_sync_add.php');
$I->seeBreadcrumb('Map Classes');

$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->click('Submit');

// After adding, we're redirected to edit page

$I->seeBreadcrumb('Map Classes');
$I->dontSeeErrors();
$I->click('Submit');

// Test Sync Now Action (DataTable action) ------------

$I->amOnModulePage('Timetable Admin', 'courseEnrolment_sync.php', array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));

$I->click('Sync Now');
$I->seeBreadcrumb('Sync Now');
$I->dontSeeErrors();

try {
    $I->see('Proceed');
    $I->click('Proceed');
    $I->see('Your request was completed successfully.', '.success');
} catch (Exception $e) {
}

// Cleanup: Delete the sync mapping -------------------

$I->amOnModulePage('Timetable Admin', 'courseEnrolment_sync.php', array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));

$I->click('Delete');
$I->seeInCurrentUrl('courseEnrolment_sync_delete.php');
$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');

