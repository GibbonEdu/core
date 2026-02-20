<?php
/**
 * @covers modules/Students/report_privacy_student.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Privacy');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_privacy_student.php');
$I->seeBreadcrumb('Privacy Choices by Student');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
