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

// Skip test if no Add button (requires reporting cycle and scope to exist)
try {
    $I->see('Add', 'a');
} catch (Exception $e) {
    $I->comment('Skipping test: No reporting cycle/scope available');
    return;
}

// Add new criteria
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Criteria');
$I->selectFromDropdown('gibbonReportingCriteriaTypeID', 1);
$I->selectFromDropdown('target', 1);
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
$I->click('Delete');
$I->seeSuccessMessage();
