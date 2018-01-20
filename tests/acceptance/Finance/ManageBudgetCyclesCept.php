<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'budgetCycles_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Budget Cycle');

$addFormValues = array(
    'name' => 'Testing',
    'status' => 'Upcoming',
    'sequenceNumber' => '999',
    'dateStart' => '01/01/2020',
    'dateEnd' => '01/12/2020',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceBudgetCycleID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'budgetCycles_manage_edit.php', array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID));
$I->seeBreadcrumb('Edit Budget Cycle');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name' => 'Testing 2',
    'status' => 'Current',
    'sequenceNumber' => '998',
    'dateStart' => '01/01/2010',
    'dateEnd' => '01/12/2030',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Finance', 'budgetCycles_manage_delete.php', array('gibbonFinanceBudgetCycleID' => $gibbonFinanceBudgetCycleID));
$I->seeBreadcrumb('Delete Budget Cycle');

$I->click('Yes');
$I->seeSuccessMessage();
