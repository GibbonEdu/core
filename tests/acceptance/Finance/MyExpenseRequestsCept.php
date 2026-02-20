<?php
/**
 * @covers modules/Finance/expenseRequest_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view my expense requests');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'expenseRequest_manage.php');
$I->seeBreadcrumb('My Expense Requests');

// TODO: This test requires:
// - A current budget cycle to exist
// - User to have budget access rights
// Add full test once these prerequisites are met
