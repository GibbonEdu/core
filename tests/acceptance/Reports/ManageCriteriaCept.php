<?php
/**
 * @covers modules/Reports/reporting_criteria_manage.php
 * @covers modules/Reports/reporting_criteria_manage_add.php
 * @covers modules/Reports/reporting_criteria_manage_edit.php
 * @covers modules/Reports/reporting_criteria_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage criteria with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reporting_criteria_manage.php');
$I->seeBreadcrumb('Manage Criteria');

// Add new criteria
$I->clickLink('Add');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Criteria');
$I->selectFromDropdown('gibbonReportingCycleID', 2);
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the criteria
$gibbonReportingCriteriaID = $I->grabEditIDFromURL();
$I->amOnModulePage('Reports', 'reporting_criteria_manage_edit.php', ['gibbonReportingCriteriaID' => $gibbonReportingCriteriaID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Criteria');
$I->fillField('name', 'Updated Criteria');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the criteria
$I->amOnModulePage('Reports', 'reporting_criteria_manage_delete.php', ['gibbonReportingCriteriaID' => $gibbonReportingCriteriaID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
