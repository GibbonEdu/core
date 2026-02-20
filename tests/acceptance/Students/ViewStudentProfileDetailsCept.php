<?php
/**
 * @covers modules/Students/student_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student profile details');
$I->loginAsAdmin();

// Get a student
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Students', 'student_view_details.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('View Student Profile');
