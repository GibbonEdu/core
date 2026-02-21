<?php
/**
 * @covers modules/Timetable Admin/ttDates.php
 * @covers modules/Timetable Admin/ttDates_edit.php
 * @covers modules/Timetable Admin/ttDates_edit_add.php
 * @covers modules/Timetable Admin/ttDates_edit_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage timetable dates with nested day management');
$I->loginAsAdmin();

// Get the current school year ID
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Timetable Admin', 'ttDates.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Tie Days to Dates');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Create test timetable and day for testing
$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

$gibbonTTID = $I->haveInDatabase('gibbonTT', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Timetable',
    'nameShort' => 'TT',
    'nameShortDisplay' => 'Day Of The Week',
    'gibbonYearGroupIDList' => $gibbonYearGroupID,
    'active' => 'Y',
]);

$gibbonTTColumnID = $I->haveInDatabase('gibbonTTColumn', [
    'name' => 'Test Column',
    'nameShort' => 'TC',
]);

$gibbonTTDayID = $I->haveInDatabase('gibbonTTDay', [
    'gibbonTTID' => $gibbonTTID,
    'gibbonTTColumnID' => $gibbonTTColumnID,
    'name' => 'Test Day',
    'nameShort' => 'TD',
    'color' => '#3A6CA8',
    'fontColor' => '#FFFFFF',
]);

// Get a school day date for testing
// Find a date within the current school year term
$termFirstDay = $I->grabFromDatabase('gibbonSchoolYearTerm', 'firstDay', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);

// Use the first day of the term as our test date
$dateStamp = strtotime($termFirstDay);

// Test Edit Days in Date ------------------------------

$I->amOnModulePage('Timetable Admin', 'ttDates_edit.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'dateStamp' => $dateStamp
]);
$I->seeBreadcrumb('Edit Days in Date');
$I->dontSeeErrors();

// Test Add Day to Date --------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Day to Date');
$I->dontSeeErrors();

// Select the timetable day to add
$I->selectFromDropdown('gibbonTTDayID', 1);
$I->submitForm('#content form', []);
$I->seeSuccessMessage();

// Use the gibbonTTDayID we created earlier for the delete test

// Test Delete Day from Date ---------------------------

$I->amOnModulePage('Timetable Admin', 'ttDates_edit_delete.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'dateStamp' => $dateStamp,
    'gibbonTTDayID' => $gibbonTTDayID
]);

$I->click('Delete');
$I->seeSuccessMessage();
