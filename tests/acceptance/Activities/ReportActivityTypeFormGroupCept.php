<?php
/**
 * @covers modules/Activities/report_activityType_formGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Activity Type by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_activityType_formGroup.php');
$I->seeBreadcrumb('Activity Type by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
