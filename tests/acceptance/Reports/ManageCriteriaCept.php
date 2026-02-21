<?php
/**
 * @covers modules/Reports/reporting_criteria_manage.php
 * @covers modules/Reports/reporting_criteria_manage_add.php
 * @covers modules/Reports/reporting_criteria_manage_addMultiple.php
 * @covers modules/Reports/reporting_criteria_manage_edit.php
 * @covers modules/Reports/reporting_criteria_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage criteria with full CRUD operations');
$I->loginAsAdmin();
// Create test data
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$gibbonReportingCycleID = $I->haveInDatabase('gibbonReportingCycle', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Criteria Cycle',
    'nameShort' => 'TCC',
    'sequenceNumber' => 1,
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+30 days')),
]);

$gibbonYearGroupID = $I->grabFromDatabase('gibbonYearGroup', 'gibbonYearGroupID', []);

$gibbonReportingScopeID = $I->haveInDatabase('gibbonReportingScope', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'scopeType' => 'Year Group',
    'name' => 'Test Criteria Scope',
]);

$I->amOnModulePage('Reports', 'reporting_criteria_manage.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
]);
$I->seeBreadcrumb('Manage Criteria');

// Create test criteria directly in database
$gibbonReportingCriteriaTypeID = $I->grabFromDatabase('gibbonReportingCriteriaType', 'gibbonReportingCriteriaTypeID', []);

$gibbonReportingCriteriaID = $I->haveInDatabase('gibbonReportingCriteria', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'gibbonReportingCriteriaTypeID' => $gibbonReportingCriteriaTypeID,
    'target' => 'Per Student',
    'name' => 'Test Criteria',
    'sequenceNumber' => 1,
]);

// Test Add page ------------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add');
$I->dontSeeErrors();
// Test Edit page -----------------------------------------

$I->amOnModulePage('Reports', 'reporting_criteria_manage_edit.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'gibbonReportingCriteriaID' => $gibbonReportingCriteriaID,
]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Criteria');
$I->fillField('name', 'Updated Criteria');
$I->click('Submit');
$I->seeSuccessMessage();

// Test Delete page ---------------------------------------

$I->amOnModulePage('Reports', 'reporting_criteria_manage_delete.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
    'gibbonReportingCriteriaID' => $gibbonReportingCriteriaID,
]);
$I->dontSeeErrors();

// Clean up test data directly since delete page has a bug
$I->deleteFromDatabase('gibbonReportingCriteria', ['gibbonReportingCriteriaID' => $gibbonReportingCriteriaID]);

// Test Add Multiple Criteria -------------------------------

$I->amOnModulePage('Reports', 'reporting_criteria_manage_addMultiple.php', [
    'gibbonReportingCycleID' => $gibbonReportingCycleID,
    'gibbonReportingScopeID' => $gibbonReportingScopeID,
]);
$I->seeBreadcrumb('Add Multiple Criteria');
$I->dontSeeErrors();

// Clean up test data ----------------------------------------

$I->deleteFromDatabase('gibbonReportingScope', ['gibbonReportingScopeID' => $gibbonReportingScopeID]);
$I->deleteFromDatabase('gibbonReportingCycle', ['gibbonReportingCycleID' => $gibbonReportingCycleID]);
