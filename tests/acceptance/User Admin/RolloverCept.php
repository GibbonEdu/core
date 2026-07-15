<?php
/**
 * @covers modules/User Admin/rollover.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View rollover');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'rollover.php');
$I->seeBreadcrumb('Rollover');
