<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage users');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'user_manage.php');
$I->seeBreadcrumb('Manage Users');
