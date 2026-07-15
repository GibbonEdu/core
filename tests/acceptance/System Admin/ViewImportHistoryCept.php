<?php
/**
 * @covers modules/System Admin/import_history.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View import history');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'import_history.php');
$I->seeBreadcrumb('Import History');
