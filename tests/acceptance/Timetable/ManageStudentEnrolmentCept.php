<?php
/**
 * @covers modules/Timetable/studentEnrolment_manage.php
 * @covers modules/Timetable/studentEnrolment_manage_edit.php
 * @covers modules/Timetable/studentEnrolment_manage_edit_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage student enrolment in a course class');
$I->loginAsAdmin();

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);
$gibbonActionID = $I->grabFromDatabase('gibbonAction', 'gibbonActionID', ['name' => 'Manage Student Enrolment']);
$gibbonPermissionID = $I->haveInDatabase('gibbonPermission', ['gibbonRoleID' => 1, 'gibbonActionID' => $gibbonActionID]);

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get a department
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', ['type' => 'Learning Area']);

$I->haveInDatabase('gibbonDepartmentStaff', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonPersonID' => $gibbonPersonID,
    'role' => 'Coordinator',
]);

// Get a course in that department
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);

// Get a class in that course
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', [
    'gibbonCourseID' => $gibbonCourseID
]);

// Get a student enrolment
$gibbonPersonIDStudent = $I->grabFromDatabase('gibbonCourseClassPerson', 'gibbonPersonID', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'role' => 'Student'
]);

// Edit ------------------------------------------------
$I->amOnModulePage('Timetable', 'studentEnrolment_manage_edit.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonCourseID' => $gibbonCourseID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Test nested Edit action (if student enrolment exists)
if ($gibbonPersonIDStudent) {
    $I->amOnModulePage('Timetable', 'studentEnrolment_manage_edit_edit.php', [
        'gibbonPersonID' => $gibbonPersonIDStudent,
        'gibbonCourseClassID' => $gibbonCourseClassID,
        'gibbonCourseID' => $gibbonCourseID,
        'gibbonSchoolYearID' => $gibbonSchoolYearID
    ]);
    $I->seeBreadcrumb('Edit');
    $I->dontSeeErrors();
}

$I->deleteFromDatabase('gibbonPermission', ['permissionID' => $gibbonPermissionID]);
