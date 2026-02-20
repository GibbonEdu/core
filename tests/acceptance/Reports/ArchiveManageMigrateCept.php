<?php
/**
 * @covers modules/Reports/archive_manage_migrate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Migrate Reports');
$I->loginAsAdmin();

$I->amOnModulePage('Reports', 'archive_manage_migrate.php');
$I->seeBreadcrumb('Migrate Reports');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
