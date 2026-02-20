<?php
/**
 * @covers modules/Students/student_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student profile');
$I->loginAsAdmin();

// Get a student
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->amOnModulePage('Students', 'student_view.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeBreadcrumb('View Student Profile');
