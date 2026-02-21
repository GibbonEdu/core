<?php
/**
 * @covers modules/Planner/units_edit_working_add.php
 * @covers modules/Planner/units_edit_working_copyback.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Add Lessons to Working Copy');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get a course, class, and unit
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', [
    'gibbonCourseID' => $gibbonCourseID
]);
$gibbonUnitID = $I->grabFromDatabase('gibbonUnit', 'gibbonUnitID', [
    'gibbonCourseID' => $gibbonCourseID
]);
$gibbonUnitClassID = $I->grabFromDatabase('gibbonUnitClass', 'gibbonUnitClassID', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonUnitID' => $gibbonUnitID
]);

$I->amOnModulePage('Planner', 'units_edit_working_add.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonUnitID' => $gibbonUnitID,
    'gibbonUnitClassID' => $gibbonUnitClassID
]);
$I->seeBreadcrumb('Add Lessons');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
