<?php
/**
 * @covers modules/Reports/reporting_cycles_manage.php
 * @covers modules/Reports/reporting_cycles_manage_add.php
 * @covers modules/Reports/reporting_cycles_manage_duplicate.php
 * @covers modules/Reports/reporting_cycles_manage_edit.php
 * @covers modules/Reports/reporting_cycles_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage reporting cycles with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_cycles_manage.php');
$I->seeBreadcrumb('Manage Reporting Cycles');

// Add a new reporting cycle
$I->click('Add');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Reporting Cycle');
$I->fillField('nameShort', 'Test');
$I->fillField('dateStart', date('Y-m-d'));
$I->fillField('dateEnd', date('Y-m-d'));

$I->click('Submit');
$I->seeSuccessMessage();

// Edit the reporting cycle
$gibbonReportingCycleID = $I->grabEditIDFromURL();
$I->amOnModulePage('Reports', 'reporting_cycles_manage_edit.php', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Reporting Cycle');
$I->fillField('name', 'Updated Reporting Cycle');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the reporting cycle
$I->amOnModulePage('Reports', 'reporting_cycles_manage_delete.php', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
$I->click('Delete');
$I->seeSuccessMessage();

// Test Duplicate Reporting Cycle ---------------------------

// Create a new cycle to duplicate
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Cycle to Duplicate',
    'nameShort' => 'Dup',
    'sequenceNumber' => 1,
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
]);

$I->amOnModulePage('Reports', 'reporting_cycles_manage_duplicate.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
]);
$I->seeBreadcrumb('Duplicate Reporting Cycle');
$I->dontSeeErrors();

// Clean up
$I->deleteFromDatabase('gibbonReportingCycle', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
