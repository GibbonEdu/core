<?php
/**
 * @covers modules/System Admin/activeSessions.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View active sessions');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'activeSessions.php');
$I->seeBreadcrumb('Active Sessions');
