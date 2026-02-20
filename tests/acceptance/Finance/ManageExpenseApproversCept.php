<?php
/**
 * @covers modules/Finance/expenseApprovers_manage.php
 * @covers modules/Finance/expenseApprovers_manage_add.php
 * @covers modules/Finance/expenseApprovers_manage_edit.php
 * @covers modules/Finance/expenseApprovers_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete an expense approver');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'expenseApprovers_manage.php');
$I->seeBreadcrumb('Manage Expense Approvers');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Expense Approver');

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->selectFromDropdown('gibbonPersonID', 1);

$formValues = array(
    'sequenceNumber' => '1',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonFinanceExpenseApproverID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'expenseApprovers_manage_edit.php', array('gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID));
$I->seeBreadcrumb('Edit Expense Approver');

$formValues['sequenceNumber'] = '2';

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Finance', 'expenseApprovers_manage_delete.php', array('gibbonFinanceExpenseApproverID' => $gibbonFinanceExpenseApproverID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
