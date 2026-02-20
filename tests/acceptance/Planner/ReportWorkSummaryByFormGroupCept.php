<?php
/**
 * @covers modules/Planner/report_workSummary_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Work Summary by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Planner', 'report_workSummary_byFormGroup.php');
$I->seeBreadcrumb('Work Summary by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
