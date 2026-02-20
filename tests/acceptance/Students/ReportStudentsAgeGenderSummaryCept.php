<?php
/**
 * @covers modules/Students/report_students_ageGenderSummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Age Gender Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_students_ageGenderSummary.php');
$I->seeBreadcrumb('Age & Gender Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
