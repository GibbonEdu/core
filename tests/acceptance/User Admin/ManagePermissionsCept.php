<?php
/**
 * @covers modules/User Admin/permission_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage permissions');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'permission_manage.php');
$I->seeBreadcrumb('Manage Permissions');
