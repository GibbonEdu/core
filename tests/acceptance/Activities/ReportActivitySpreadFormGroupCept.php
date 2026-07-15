<?php
/**
 * @covers modules/Activities/report_activitySpread_formGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Activity Spread by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_activitySpread_formGroup.php');
$I->seeBreadcrumb('Activity Spread by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
