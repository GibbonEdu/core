<?php
/**
 * @covers modules/Students/report_myStudentHistory.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check My Student History');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_myStudentHistory.php');
$I->seeBreadcrumb('My Student History');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
