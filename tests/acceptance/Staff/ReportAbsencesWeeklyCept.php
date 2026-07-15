<?php
/**
 * @covers modules/Staff/report_absences_weekly.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Weekly Absences');
$I->loginAsAdmin();

$I->amOnModulePage('Staff', 'report_absences_weekly.php');
$I->seeBreadcrumb('Weekly Absences');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
