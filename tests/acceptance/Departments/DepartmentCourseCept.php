<?php
/**
 * @covers modules/Departments/department_course.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view a department course');
$I->loginAsAdmin();

// Get a course with a department
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', []);
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', ['gibbonDepartmentID' => $gibbonDepartmentID]);

$I->amOnModulePage('Departments', 'department_course.php', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonCourseID' => $gibbonCourseID
]);
$I->seeBreadcrumb('Departments');
$I->see('Units', 'h2');
