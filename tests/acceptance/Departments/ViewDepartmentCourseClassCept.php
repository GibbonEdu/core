<?php
/**
 * @covers modules/Departments/department_course_class.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View department course class');
$I->loginAsAdmin();

// Get a department, course, and class
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', []);
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', ['gibbonCourseID' => $gibbonCourseID]);

$I->amOnModulePage('Departments', 'department_course_class.php', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonCourseClassID' => $gibbonCourseClassID
]);
$I->dontSeeErrors();
