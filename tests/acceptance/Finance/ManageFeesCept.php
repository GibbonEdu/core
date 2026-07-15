<?php
/**
 * @covers modules/Finance/fees_manage.php
 * @covers modules/Finance/fees_manage_add.php
 * @covers modules/Finance/fees_manage_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add and edit fees');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Finance', 'fees_manage.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Manage Fees');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Fee');

$I->selectFromDropdown('gibbonFinanceFeeCategoryID', 1);

$formValues = [
    'name' => 'Test Fee',
    'nameShort' => 'TF',
    'active' => 'Y',
    'description' => 'Test fee description',
    'fee' => '100.00',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFinanceFeeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'fees_manage_edit.php', [
    'gibbonFinanceFeeID' => $gibbonFinanceFeeID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('Edit Fee');

$I->seeInFormFields('#content form', [
    'name' => 'Test Fee',
    'nameShort' => 'TF',
]);

$I->selectFromDropdown('gibbonFinanceFeeCategoryID', 2);

$formValues = [
    'name' => 'Updated Test Fee',
    'nameShort' => 'UTF',
    'active' => 'N',
    'description' => 'Updated description',
    'fee' => '150.00',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();
