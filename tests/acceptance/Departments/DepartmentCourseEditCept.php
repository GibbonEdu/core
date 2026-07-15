<?php
/**
 * @covers modules/Departments/department_course_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('edit a department course');
$I->loginAsAdmin();

// Get admin person ID
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);

// Try to find a department where admin is already a coordinator
$gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartmentStaff', 'gibbonDepartmentID', [
    'gibbonPersonID' => $gibbonPersonID
]);

// If no department found, get any department and add admin as coordinator
if (empty($gibbonDepartmentID)) {
    $gibbonDepartmentID = $I->grabFromDatabase('gibbonDepartment', 'gibbonDepartmentID', []);
    
    // Insert directly using SQL to ensure it's committed
    $I->haveInDatabase('gibbonDepartmentStaff', [
        'gibbonDepartmentID' => $gibbonDepartmentID,
        'gibbonPersonID' => $gibbonPersonID,
        'role' => 'Coordinator',
    ]);
    
    // Force a page reload to ensure the database change is visible
    $I->amOnModulePage('Departments', 'departments.php');
}

// Get a course associated with this department
$gibbonCourseID = $I->grabFromDatabase('gibbonCourse', 'gibbonCourseID', [
    'gibbonDepartmentID' => $gibbonDepartmentID
]);

$I->amOnModulePage('Departments', 'department_course_edit.php', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonCourseID' => $gibbonCourseID
]);
$I->seeBreadcrumb('Edit Course');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Edit Course Description -----------------------------

$formValues = [
    'description' => '<p>Updated course description for testing purposes.</p>',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original values -----------------------------

$I->amOnModulePage('Departments', 'department_course_edit.php', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonCourseID' => $gibbonCourseID
]);

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();

// Clean up --------------------------------------------

$I->amOnModulePage('Departments', 'department_course.php', [
    'gibbonDepartmentID' => $gibbonDepartmentID,
    'gibbonCourseID' => $gibbonCourseID
]);
