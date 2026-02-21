<?php
/**
 * @covers modules/Finance/expenses_manage.php
 * @covers modules/Finance/expenses_manage_add.php
 * @covers modules/Finance/expenses_manage_edit.php
 * @covers modules/Finance/expenses_manage_approve.php
 * @covers modules/Finance/expenses_manage_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit, approve and view expenses');
$I->loginAsAdmin();

// Get a budget cycle
$gibbonFinanceBudgetCycleID = $I->grabFromDatabase('gibbonFinanceBudgetCycle', 'gibbonFinanceBudgetCycleID', ['status' => 'Current']);

if (!$gibbonFinanceBudgetCycleID) {
    $I->comment('No current budget cycle found, skipping expense management test');
    return;
}

$I->amOnModulePage('Finance', 'expenses_manage.php', ['gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID]);
$I->seeBreadcrumb('Manage Expenses');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Expense');

// Select a budget
$I->selectFromDropdown('gibbonFinanceBudgetID', 1);

$formValues = [
    'title' => 'Test Expense',
    'status' => 'Approved',
    'body' => 'Test expense description',
    'cost' => '500.00',
    'countAgainstBudget' => 'Y',
    'purchaseBy' => 'School',
    'purchaseDetails' => 'Test purchase details',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceExpenseID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'expenses_manage_edit.php', [
    'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID,
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID
]);
$I->seeBreadcrumb('Edit Expense');

$I->seeInField('title', 'Test Expense');

// Change status to Paid
$I->selectOption('status', 'Paid');

// Fill in payment information
$I->fillField('paymentDate', date('d/m/Y'));
$I->fillField('paymentAmount', '500.00');
$I->selectFromDropdown('gibbonPersonIDPayment', 1);
$I->selectOption('paymentMethod', 'Bank Transfer');
$I->fillField('paymentID', 'TEST123');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// View ------------------------------------------------
$I->amOnModulePage('Finance', 'expenses_manage_view.php', [
    'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID,
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID
]);
$I->seeBreadcrumb('View Expense');

$I->seeInField('title', 'Test Expense');
$I->seeInField('status', 'Paid');
