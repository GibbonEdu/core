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

// Get a student
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingstudent']);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Students', 'student_view_details.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('View Student Profiles');
