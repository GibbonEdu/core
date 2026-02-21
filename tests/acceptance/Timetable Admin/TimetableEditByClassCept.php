<?php
/**
 * @covers modules/Timetable Admin/tt_edit_byClass.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('edit timetable by class');
$I->loginAsAdmin();

// Get an existing timetable
$gibbonTTID = $I->grabFromDatabase('gibbonTT', 'gibbonTTID', []);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonTT', 'gibbonSchoolYearID', ['gibbonTTID' => $gibbonTTID]);

$I->amOnModulePage('Timetable Admin', 'tt_edit_byClass.php', [
    'gibbonTTID' => $gibbonTTID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Edit Timetable by Class');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Test Class Selection --------------------------------

$I->selectFromDropdown('gibbonCourseClassID', 1);
$I->click('Next');
$I->dontSeeErrors();

// Verify the form loaded with the selected class
$I->seeBreadcrumb('Edit Timetable by Class');
$I->see('This is an administrative tool to assist with timetable changes');

