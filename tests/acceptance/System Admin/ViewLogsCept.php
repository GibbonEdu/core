<?php
/**
 * @covers modules/System Admin/logs_view.php
 * @covers modules/System Admin/logs_view_purge.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View system logs');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'logs_view.php');
$I->seeBreadcrumb('View Logs');

// Test Purge Page (DataTable action) ------------------

$I->amOnModulePage('System Admin', 'logs_view_purge.php');
$I->dontSeeErrors();
