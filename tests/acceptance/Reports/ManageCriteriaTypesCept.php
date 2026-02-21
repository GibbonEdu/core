<?php
/**
 * @covers modules/Reports/criteriaTypes_manage.php
 * @covers modules/Reports/criteriaTypes_manage_add.php
 * @covers modules/Reports/criteriaTypes_manage_edit.php
 * @covers modules/Reports/criteriaTypes_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a criteria type');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'criteriaTypes_manage.php');
$I->seeBreadcrumb('Manage Criteria Types');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Criteria Type');

$formValues = array(
    'name' => 'Test Criteria Type',
    'active' => 'Y',
    'valueType' => 'Text',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonReportingCriteriaTypeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Reports', 'criteriaTypes_manage_edit.php', array(
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID
));
$I->seeBreadcrumb('Edit Criteria Type');

$I->seeInFormFields('#content form', array(
    'name' => 'Test Criteria Type',
    'active' => 'Y',
));

$formValues = array(
    'name' => 'Updated Criteria Type',
    'active' => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Reports', 'criteriaTypes_manage_delete.php', array(
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID
));

$I->click('Delete');
$I->seeSuccessMessage();
