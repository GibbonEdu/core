<?php
/**
 * @covers modules/Staff/coverage_planner.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View daily coverage planner');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'coverage_planner.php');
$I->seeBreadcrumb('Daily Coverage Planner');
