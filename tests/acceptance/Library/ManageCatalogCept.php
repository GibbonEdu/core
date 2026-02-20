<?php
/**
 * @covers modules/Library/library_manage_catalog.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage catalog');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'library_manage_catalog.php');
$I->seeBreadcrumb('Manage Catalog');
