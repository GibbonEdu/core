<?php
/**
 * @covers modules/User Admin/role_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage roles');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'role_manage.php');
$I->seeBreadcrumb('Manage Roles');
