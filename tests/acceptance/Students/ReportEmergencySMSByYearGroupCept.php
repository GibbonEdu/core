<?php
/**
 * @covers modules/Students/report_emergencySMS_byYearGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Emergency SMS by Year Group');
$I->loginAsAdmin();

$I->amOnModulePage('Students', 'report_emergencySMS_byYearGroup.php');
$I->seeBreadcrumb('Emergency SMS by Year Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
