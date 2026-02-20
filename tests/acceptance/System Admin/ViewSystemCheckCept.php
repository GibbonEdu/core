<?php
/**
 * @covers modules/System Admin/systemCheck.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View system check');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'systemCheck.php');
$I->seeBreadcrumb('System Check');
