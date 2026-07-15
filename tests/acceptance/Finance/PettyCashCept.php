<?php
/**
 * @covers modules/Finance/pettyCash.php
 * @covers modules/Finance/pettyCash_action.php
 * @covers modules/Finance/pettyCash_addEdit.php
 * @covers modules/Finance/pettyCash_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit, action and delete petty cash transactions');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'pettyCash.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Petty Cash');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Transaction');

// Select a person
$I->selectFromDropdown('gibbonPersonID', 1);

$formValues = [
    'amount' => '50.00',
    'reason' => 'Supplies',
    'actionRequired' => 'Repay',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinancePettyCashID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'pettyCash_addEdit.php', [
    'mode' => 'edit',
    'gibbonFinancePettyCashID' => $gibbonFinancePettyCashID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Edit Transaction');

$I->seeInField('amount', '50.00');

// Update amount
$I->fillField('amount', '75.00');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Action (Mark as Complete) --------------------------
$I->amOnModulePage('Finance', 'pettyCash_action.php', [
    'gibbonFinancePettyCashID' => $gibbonFinancePettyCashID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'action' => 'Complete'
]);

$I->fillField('statusDate', date('d/m/Y'));
$I->fillField('statusTime', date('H:i'));
$I->fillField('notes', 'Transaction completed');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
// Create a new transaction to delete
$I->amOnModulePage('Finance', 'pettyCash.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->clickNavigation('Add');

$I->selectFromDropdown('gibbonPersonID', 1);

$formValues = [
    'amount' => '25.00',
    'reason' => 'Supplies',
    'actionRequired' => 'None',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinancePettyCashIDToDelete = $I->grabEditIDFromURL();

$I->amOnModulePage('Finance', 'pettyCash_delete.php', [
    'gibbonFinancePettyCashID' => $gibbonFinancePettyCashIDToDelete
]);

$I->click('Delete');
$I->seeSuccessMessage();
