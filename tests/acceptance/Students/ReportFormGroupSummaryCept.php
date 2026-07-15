<?php
/**
 * @covers modules/Students/report_formGroupSummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Form Group Summary');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_formGroupSummary.php');
$I->seeBreadcrumb('Form Group Summary');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
