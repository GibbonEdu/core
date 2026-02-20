<?php
/**
 * @covers modules/Students/student_view.php
 * @covers modules/Students/student_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student profile');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'student_view.php');
$I->seeBreadcrumb('View Student Profiles');

// Database Seed  ------------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingstudent']);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonFormGroupID = $I->grabFromDatabase('gibbonFormGroup', 'gibbonFormGroupID');
$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID');

$gibbonStudentEnrolmentID = $I->haveInDatabase('gibbonStudentEnrolment', [
    'gibbonPersonID'     => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonFormGroupID'  => $gibbonFormGroupID,
    'gibbonYearGroupID'  => $gibbonYearGroupID,
]);


// Test View Page  ------------------------------

$I->amOnModulePage('Students', 'student_view_details.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('View Student Profiles');

// Database Cleanup  ------------------------------

$I->deleteFromDatabase('gibbonStudentEnrolment', ['gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID]);
