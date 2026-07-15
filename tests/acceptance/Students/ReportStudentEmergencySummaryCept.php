<?php
/**
 * @covers modules/Students/report_student_emergencySummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Emergency Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_student_emergencySummary.php');
$I->seeBreadcrumb('Student Emergency Data Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
