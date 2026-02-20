<?php
/**
 * @covers modules/Staff/coverage_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage staff coverage');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'coverage_manage.php');
$I->seeBreadcrumb('Manage Staff Coverage');
