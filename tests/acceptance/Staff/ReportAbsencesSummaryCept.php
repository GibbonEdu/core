<?php
/**
 * @covers modules/Staff/report_absences_summary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Absences Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Staff', 'report_absences_summary.php');
$I->seeBreadcrumb('Staff Absence Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
