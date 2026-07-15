<?php
/**
 * @covers modules/Individual Needs/in_archive.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Archive records');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'in_archive.php');
$I->seeBreadcrumb('Archive Records');
