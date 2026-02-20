<?php
/**
 * @covers modules/Departments/departments.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view all departments');
$I->loginAsAdmin();
$I->amOnModulePage('Departments', 'departments.php');
$I->seeBreadcrumb('View All');
$I->see('Learning Areas', 'h2');
