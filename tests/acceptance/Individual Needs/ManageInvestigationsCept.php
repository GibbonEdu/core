<?php
/**
 * @covers modules/Individual Needs/investigations_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage investigations');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'investigations_manage.php');
$I->seeBreadcrumb('Manage Investigations');
