<?php
/**
 * @covers modules/Reports/reporting_access_manage.php
 * @covers modules/Reports/reporting_access_manage_add.php
 * @covers modules/Reports/reporting_access_manage_edit.php
 * @covers modules/Reports/reporting_access_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete reporting access');
$I->loginAsAdmin();

// Setup: Create a reporting cycle and scope ----------
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

$gibbonReportingScopeID = $I->haveInDatabase('gibbonReportingScope', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'name' => 'Test Scope',
    'scopeType' => 'Year Group',
]);

$I->amOnModulePage('Reports', 'reporting_access_manage.php');
$I->seeBreadcrumb('Manage Access');

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonReportingCycleID', 1);
$I->submitForm('#archiveByReport', []);
$I->dontSeeErrors();

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Access');

$I->selectFromDropdown('gibbonRoleIDList', 1);
$I->selectFromDropdown('gibbonReportingCycleID', 1);
$I->selectFromDropdown('gibbonReportingScopeID', 1);

$formValues = array(
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
    'canWrite' => 'Y',
    'canProofRead' => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonReportingAccessID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Reports', 'reporting_access_manage_edit.php', array(
    'gibbonReportingAccessID' => $gibbonReportingAccessID
));
$I->seeBreadcrumb('Edit Access');

$I->seeInFormFields('#content form', array(
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
    'canWrite' => 'Y',
    'canProofRead' => 'N',
));

$formValues = array(
    'dateStart' => date('Y-m-d', strtotime('+1 day')),
    'dateEnd' => date('Y-m-d', strtotime('+31 days')),
    'canWrite' => 'N',
    'canProofRead' => 'Y',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Reports', 'reporting_access_manage_delete.php', array(
    'gibbonReportingAccessID' => $gibbonReportingAccessID
));

$I->click('Delete');
$I->seeSuccessMessage();

// Cleanup: Delete test reporting cycle and scope -----
$I->amOnModulePage('Reports', 'reporting_cycles_manage_delete.php', array(
    'gibbonReportingCycleID' => $gibbonReportingCycleID
));

$I->click('Delete');
$I->seeSuccessMessage();

