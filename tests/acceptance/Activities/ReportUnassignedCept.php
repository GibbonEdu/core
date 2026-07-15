<?php
/**
 * @covers modules/Activities/report_unassigned.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Unassigned Students');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_unassigned.php');
$I->seeBreadcrumb('View Unassigned Staff');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
