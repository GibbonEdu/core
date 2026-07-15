<?php
/**
 * @covers modules/User Admin/district_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage districts');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'district_manage.php');
$I->seeBreadcrumb('Manage Districts');
