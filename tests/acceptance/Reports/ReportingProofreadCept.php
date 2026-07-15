<?php
/**
 * @covers modules/Reports/reporting_proofread.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check reporting proofread page');
$I->loginAsAdmin();

// Reporting Proofread ---------------------------------------

$I->amOnModulePage('Reports', 'reporting_proofread.php');
$I->seeBreadcrumb('Proof Read');
$I->dontSeeErrors();
