<?php
/**
 * @covers modules/System Admin/update.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check System Update');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'update.php');
$I->seeBreadcrumb('Update');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
