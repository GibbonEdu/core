<?php
/**
 * @covers modules/Activities/report_notSignedUp.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Students Not Signed Up');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_notSignedUp.php');
$I->seeBreadcrumb('Students Not Signed Up');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
