<?php
/**
 * @covers modules/Reports/reporting_cycles_manage.php
 * @covers modules/Reports/reporting_cycles_manage_add.php
 * @covers modules/Reports/reporting_cycles_manage_edit.php
 * @covers modules/Reports/reporting_cycles_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage reporting cycles with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_cycles_manage.php');
$I->seeBreadcrumb('Manage Reporting Cycles');

// Add a new reporting cycle
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Reporting Cycle');
$I->selectFromDropdown('gibbonSchoolYearID', 2);
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
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
