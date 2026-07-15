<?php
/**
 * @covers modules/Staff/report_coverage_summary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Coverage Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Staff', 'report_coverage_summary.php');
$I->seeBreadcrumb('Staff Coverage Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
