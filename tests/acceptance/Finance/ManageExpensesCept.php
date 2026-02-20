<?php
/**
 * @covers modules/Finance/expenses_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage expenses');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'expenses_manage.php');
$I->seeBreadcrumb('Manage Expenses');

// TODO: This test requires:
// - A current budget cycle to exist
// - Expense approvers to be configured
// - Budgets with proper access rights
// Add full CRUD test once these prerequisites are met
