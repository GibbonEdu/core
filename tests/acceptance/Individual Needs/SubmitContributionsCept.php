<?php
/**
 * @covers modules/Individual Needs/investigations_submit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Submit contributions');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'investigations_submit.php');
$I->seeBreadcrumb('Submit Contributions');
