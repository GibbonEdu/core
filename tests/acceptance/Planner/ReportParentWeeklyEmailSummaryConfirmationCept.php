<?php
/**
 * @covers modules/Planner/report_parentWeeklyEmailSummaryConfirmation.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Parent Weekly Email Summary Confirmation');
$I->loginAsAdmin();

$I->amOnModulePage('Planner', 'report_parentWeeklyEmailSummaryConfirmation.php');
$I->seeBreadcrumb('Parent Weekly Email Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
