<?php
/**
 * @covers modules/Finance/feeCategories_manage.php
 * @covers modules/Finance/feeCategories_manage_add.php
 * @covers modules/Finance/feeCategories_manage_edit.php
 * @covers modules/Finance/feeCategories_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a fee category');
$I->loginAsAdmin();
$I->amOnModulePage('Finance', 'feeCategories_manage.php');
$I->seeBreadcrumb('Manage Fee Categories');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Category');

$formValues = array(
    'name' => 'Test Category ' . uniqid(),
    'nameShort' => 'TC' . uniqid(),
    'active' => 'Y',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonFinanceFeeCategoryID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Finance', 'feeCategories_manage_edit.php', array('gibbonFinanceFeeCategoryID' => $gibbonFinanceFeeCategoryID));
$I->seeBreadcrumb('Edit Category');

$I->seeInFormFields('#content form', array(
    'name' => $formValues['name'],
));

$formValues['name'] = 'Updated Category ' . uniqid();

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Finance', 'feeCategories_manage_delete.php', array('gibbonFinanceFeeCategoryID' => $gibbonFinanceFeeCategoryID));

$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
