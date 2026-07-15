<?php
/**
 * @covers modules/Planner/units_duplicate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Duplicate Unit');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get a course and unit
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$gibbonUnitID = $I->grabFromDatabase('gibbonUnit', 'gibbonUnitID', [
    'gibbonCourseID' => $gibbonCourseID
]);

$I->amOnModulePage('Planner', 'units_duplicate.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonUnitID' => $gibbonUnitID
]);
$I->seeBreadcrumb('Duplicate Unit');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
