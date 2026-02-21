<?php
/**
 * @covers modules/System Admin/import_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Import Management');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'import_manage.php');
$I->seeBreadcrumb('Import From File');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
