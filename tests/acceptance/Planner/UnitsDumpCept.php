<?php
/**
 * @covers modules/Planner/units_dump.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Dump Unit');
$I->loginAsAdmin();

// This page requires gibbonSchoolYearID, gibbonCourseID, and gibbonUnitID parameters
// We'll create test data to access it

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Create a test course
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', []);
$gibbonCourseID = $I->haveInDatabase('gibbonCourse', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'name' => 'Test Course for Dump',
    'nameShort' => 'TESTDUMP',
    'description' => 'Test course description',
    'gibbonYearGroupIDList' => '',
    'orderBy' => 0,
]);

// Create a test unit
$gibbonUnitID = $I->haveInDatabase('gibbonUnit', [
    'gibbonCourseID' => $gibbonCourseID,
    'name' => 'Test Unit',
    'description' => 'Test unit for dump',
    'tags' => '',
    'details' => '',
    'attachment' => '',
    'active' => 'Y',
    'ordering' => 0,
    'gibbonPersonIDCreator' => 1,
    'gibbonPersonIDLastEdit' => 1,
]);

$I->amOnModulePage('Planner', 'units_dump.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonUnitID' => $gibbonUnitID,
]);
$I->seeBreadcrumb('Dump Unit');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
