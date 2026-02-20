<?php
/**
 * @covers modules/System Admin/serverInfo.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View server information');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'serverInfo.php');
$I->seeBreadcrumb('Server Info');
