<?php
/**
 * @covers modules/Students/report_students_IDCards.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student ID Cards');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_students_IDCards.php');
$I->seeBreadcrumb('Student ID Cards');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// File Upload Check ------------------------------------
$gibbonPersonID = $I->grabFromDatabase('gibbonStudentEnrolment', 'gibbonPersonID', ['gibbonSchoolYearID' => $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current'])]);

$I->amOnModulePage('Students', 'report_students_IDCards.php');
$I->attachFile('file', 'attachment.jpg');
$I->submitForm('#content form', ['gibbonPersonID' => [$gibbonPersonID]], 'Search');
$I->dontSeeErrors();
