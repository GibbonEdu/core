<?php
/**
 * @covers modules/Finance/expenses_manage_processBulkExportContents.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('export expenses in bulk');
$I->loginAsAdmin();

// Get a budget cycle
$gibbonFinanceBudgetCycleID = $I->grabFromDatabase('gibbonFinanceBudgetCycle', 'gibbonFinanceBudgetCycleID', ['status' => 'Current']);

if (!$gibbonFinanceBudgetCycleID) {
    $I->comment('No current budget cycle found, skipping bulk export test');
    return;
}

// Get a budget
$gibbonFinanceBudgetID = $I->grabFromDatabase('gibbonFinanceBudget', 'gibbonFinanceBudgetID', ['active' => 'Y']);

if (!$gibbonFinanceBudgetID) {
    $I->comment('No active budget found, skipping bulk export test');
    return;
}

// Create a test expense for export
$gibbonFinanceExpenseID = $I->haveInDatabase('gibbonFinanceExpense', [
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID,
    'gibbonFinanceBudgetID' => $gibbonFinanceBudgetID,
    'gibbonPersonIDCreator' => $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', []),
    'title' => 'Test Expense for Export',
    'body' => 'Test expense description',
    'status' => 'Approved',
    'cost' => 100.00,
    'countAgainstBudget' => 'Y',
    'purchaseBy' => 'School',
    'timestampCreator' => date('Y-m-d H:i:s'),
]);

// Set the session variable for export
$I->haveHttpHeader('Cookie', 'financeExpenseExportIDs=' . serialize([$gibbonFinanceExpenseID]));

// Navigate to the export page
$I->amOnModulePage('Finance', 'expenses_manage_processBulkExportContents.php', [
    'gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID
]);

$I->dontSeeErrors();
