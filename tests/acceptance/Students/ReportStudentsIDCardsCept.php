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
