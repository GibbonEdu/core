<?php
/**
 * @covers modules/School Admin/schoolYearTerm_manage.php
 * @covers modules/School Admin/schoolYearTerm_manage_add.php
 * @covers modules/School Admin/schoolYearTerm_manage_edit.php
 * @covers modules/School Admin/schoolYearTerm_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage school year terms');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'schoolYearTerm_manage.php');
$I->seeBreadcrumb('Manage Terms');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Add Term --------------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Term');

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->selectFromDropdown('gibbonSchoolYearID', 1);

$formValues = [
    'sequenceNumber' => '99',
    'name' => 'Test Term',
    'nameShort' => 'TT',
    'firstDay' => date('Y-m-d'),
    'lastDay' => date('Y-m-d', strtotime('+30 days')),
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonSchoolYearTermID = $I->grabEditIDFromURL();

// Edit Term -------------------------------------------

$I->amOnModulePage('School Admin', 'schoolYearTerm_manage_edit.php', [
    'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID,
]);
$I->seeBreadcrumb('Edit Term');

$I->seeInField('name', 'Test Term');

$formValues = [
    'name' => 'Updated Test Term',
    'nameShort' => 'UTT',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete Term -----------------------------------------

$I->amOnModulePage('School Admin', 'schoolYearTerm_manage_delete.php', [
    'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID,
]);

$I->click('Delete');
$I->seeSuccessMessage();
