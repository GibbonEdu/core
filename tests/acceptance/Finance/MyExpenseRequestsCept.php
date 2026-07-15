<?php
/**
 * @covers modules/Finance/expenseRequest_manage.php
 * @covers modules/Finance/expenseRequest_manage_add.php
 * @covers modules/Finance/expenseRequest_manage_reimburse.php
 * @covers modules/Finance/expenseRequest_manage_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, reimburse and view expense requests');
$I->loginAsAdmin();

// Get a budget cycle
$gibbonFinanceBudgetCycleID = $I->grabFromDatabase('gibbonFinanceBudgetCycle', 'gibbonFinanceBudgetCycleID', ['status' => 'Current']);

if (!$gibbonFinanceBudgetCycleID) {
    $I->comment('No current budget cycle found, skipping expense request test');
    return;
}

$I->amOnModulePage('Finance', 'expenseRequest_manage.php', ['gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID]);
$I->seeBreadcrumb('My Expense Requests');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Expense Request');

// Select a budget
$I->selectFromDropdown('gibbonFinanceBudgetID', 1);

$formValues = [
    'title' => 'Test Expense Request',
    'body' => 'Test expense request description',
    'cost' => '250.00',
    'countAgainstBudget' => 'Y',
    'purchaseBy' => 'Self',
    'purchaseDetails' => 'Test purchase details',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceExpenseID = $I->grabEditIDFromURL();

// View ------------------------------------------------
$I->amOnModulePage('Finance', 'expenseRequest_manage_view.php', [
    'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID,
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID
]);
$I->seeBreadcrumb('View Expense Request');

$I->seeInField('title', 'Test Expense Request');
$I->seeInField('status', 'Requested');

// Approve the expense so we can reimburse it ----------
$I->updateInDatabase('gibbonFinanceExpense', ['status' => 'Approved'], ['gibbonFinanceExpenseID' => $gibbonFinanceExpenseID]);

// Reimburse -------------------------------------------
$I->amOnModulePage('Finance', 'expenseRequest_manage_reimburse.php', [
    'gibbonFinanceExpenseID' => $gibbonFinanceExpenseID,
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID,
]);
$I->seeBreadcrumb('Reimburse Expense Request');

$I->selectOption('status', 'Paid');
$I->fillField('paymentDate', date('Y-m-d'));
$I->fillField('paymentAmount', '250.00');

$I->attachFile('file', 'attachment.jpg');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$file = $I->grabFromDatabase('gibbonFinanceExpense', 'paymentReimbursementReceipt', ['gibbonFinanceExpenseID' => $gibbonFinanceExpenseID]);
$I->assertNotEmpty($file);

// Cleanup ------------------------------------------------
$I->deleteFromDatabase('gibbonFinanceExpense', ['gibbonFinanceExpenseID' => $gibbonFinanceExpenseID]);
$I->deleteFile('../'.$file);
