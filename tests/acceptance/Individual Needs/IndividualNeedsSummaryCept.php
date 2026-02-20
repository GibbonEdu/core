<?php
/**
 * @covers modules/Individual Needs/in_summary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View individual needs summary');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'in_summary.php');
$I->seeBreadcrumb('Individual Needs Summary');
