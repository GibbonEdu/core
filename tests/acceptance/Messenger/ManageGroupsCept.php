<?php
/**
 * @covers modules/Messenger/groups_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage groups');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'groups_manage.php');
$I->seeBreadcrumb('Manage Groups');
