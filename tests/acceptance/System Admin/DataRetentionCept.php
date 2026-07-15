<?php
/**
 * @covers modules/System Admin/dataRetention.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Data Retention');
$I->loginAsAdmin();

$I->amOnModulePage('System Admin', 'dataRetention.php');
$I->seeBreadcrumb('Data Retention');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
