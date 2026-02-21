<?php
/**
 * @covers modules/Staff/coverage_planner_assign.php
 * @covers modules/Staff/coverage_planner_copy.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test coverage planner actions');
$I->loginAsAdmin();

// Enable internal coverage ---------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$originalFormValues = $I->grabAllFormValues();

$newFormValues = array(
    'coverageInternal' => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Create test coverage data ---------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonPersonIDCoverage = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$gibbonStaffCoverageID = $I->haveInDatabase('gibbonStaffCoverage', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']),
    'gibbonPersonIDCoverage' => null,
    'gibbonPersonIDStatus' => $gibbonPersonID,
    'status' => 'Requested',
]);

$gibbonStaffCoverageDateID = $I->haveInDatabase('gibbonStaffCoverageDate', [
    'gibbonStaffCoverageID' => $gibbonStaffCoverageID,
    'date' => date('Y-m-d', strtotime('+1 day')),
    'allDay' => 'Y',
    'timeStart' => '09:00:00',
    'timeEnd' => '15:00:00',
]);

// Test Assign Action ----------------------------------

$I->amOnModulePage('Staff', 'coverage_planner_assign.php', [
    'gibbonStaffCoverageDateID' => $gibbonStaffCoverageDateID
]);

$I->dontSeeErrors();

// Test Copy Action ------------------------------------

$I->amOnModulePage('Staff', 'coverage_planner_copy.php', [
    'date' => date('Y-m-d', strtotime('+1 day'))
]);

$I->dontSeeErrors();

// Clean up test data ----------------------------------

$I->amOnModulePage('Staff', 'coverage_manage_delete.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->click('Delete');
$I->seeSuccessMessage();

// Restore original settings ---------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
