<?php
/**
 * @covers modules/Activities/report_notEnrolled.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Students Not Enrolled');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_notEnrolled.php');
$I->seeBreadcrumb('Students Not Enrolled');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
