<?php
/**
 * @covers modules/Reports/reporting_scopes_manage.php
 * @covers modules/Reports/reporting_scopes_manage_add.php
 * @covers modules/Reports/reporting_scopes_manage_edit.php
 * @covers modules/Reports/reporting_scopes_manage_delete.php
 * @covers modules/Reports/reporting_scopes_manage_editProcessBulk.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a reporting scope');
$I->loginAsAdmin();

// Setup: Create a reporting cycle --------------------
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Reporting Cycle',
    'nameShort' => 'Test',
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+90 days')),
    'cycleNumber' => 1,
    'cycleTotal' => 1,
]);

$I->amOnModulePage('Reports', 'reporting_scopes_manage.php', array(
    'gibbonReportingCycleID' => $gibbonReportingCycleID
));
$I->seeBreadcrumb('Reporting Scopes');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Scope');

$I->selectFromDropdown('scopeType', 1);

$formValues = array(
    'name' => 'Test Reporting Scope',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonReportingScopeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Reports', 'reporting_scopes_manage_edit.php', array(
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID
));
$I->seeBreadcrumb('Edit Scope');

$I->seeInFormFields('#content form', array(
    'name' => 'Test Reporting Scope',
));

$formValues = array(
    'name' => 'Updated Reporting Scope',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Reports', 'reporting_scopes_manage_delete.php', array(
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'gibbonReportingCycleID' => $gibbonReportingCycleID
));

$I->click('Delete');
$I->seeSuccessMessage();

// Cleanup: Delete test reporting cycle ---------------
$I->amOnModulePage('Reports', 'reporting_cycles_manage_delete.php', array(
    'gibbonReportingCycleID' => $gibbonReportingCycleID
));

$I->click('Delete');
$I->seeSuccessMessage();
