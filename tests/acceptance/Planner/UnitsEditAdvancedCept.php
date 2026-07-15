<?php
/**
 * @covers modules/Planner/units_edit_copyBack.php
 * @covers modules/Planner/units_edit_copyForward.php
 * @covers modules/Planner/units_edit_deploy.php
 * @covers modules/Planner/units_edit_smartBlockify.php
 * @covers modules/Planner/units_edit_working.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Edit Working Copy');
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

$I->amOnModulePage('Planner', 'units_edit_working.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonUnitID' => $gibbonUnitID,
    'gibbonUnitClassID' => $gibbonUnitClassID
]);
$I->seeBreadcrumb('Edit Working Copy');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
