<?php
/**
 * @covers modules/System Admin/impersonateUser.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View impersonate user page');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'impersonateUser.php');
$I->seeBreadcrumb('Impersonate User');
