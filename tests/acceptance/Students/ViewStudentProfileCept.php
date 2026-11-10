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
$I->dontSeeErrors();
$I->dontSee('Undefined array key');
$I->seeElement('a[href*="courseEnrolment_manage_byPerson_edit.php"]');
$I->dontSeeElement('a[href*="tt_manage_subscription.php"]');

$I->amOnModulePage('Students', 'student_view_details.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'subpage' => 'Timetable',
    'allStudents' => 'on',
]);
$I->dontSeeErrors();
$I->dontSee('Undefined array key');
$I->seeElement('a[href*="courseEnrolment_manage_byPerson_edit.php"][href*="allUsers=on"]');
$I->dontSeeElement('a[href*="tt_manage_subscription.php"]');

// Database Cleanup  ------------------------------

$I->deleteFromDatabase('gibbonStudentEnrolment', ['gibbonStudentEnrolmentID' => $gibbonStudentEnrolmentID]);
