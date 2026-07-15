<?php
/**
 * @covers modules/Students/report_student_medicalSummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Medical Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_student_medicalSummary.php');
$I->seeBreadcrumb('Student Medical Data Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
