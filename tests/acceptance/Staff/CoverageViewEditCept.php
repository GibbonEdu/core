<?php
/**
 * @covers modules/Staff/coverage_view_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('edit coverage from my coverage view');
$I->loginAsAdmin();

// Enable internal coverage ---------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$originalFormValues = $I->grabAllFormValues();

$newFormValues = array(
    'coverageInternal' => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Create test coverage data ---------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);

$gibbonStaffCoverageID = $I->haveInDatabase('gibbonStaffCoverage', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']),
    'gibbonPersonIDCoverage' => $gibbonPersonID,
    'gibbonPersonIDStatus' => $gibbonPersonID,
    'status' => 'Accepted',
]);

$gibbonStaffCoverageDateID = $I->haveInDatabase('gibbonStaffCoverageDate', [
    'gibbonStaffCoverageID' => $gibbonStaffCoverageID,
    'date' => date('Y-m-d', strtotime('+1 day')),
    'allDay' => 'Y',
    'timeStart' => '09:00:00',
    'timeEnd' => '15:00:00',
]);

// Edit Coverage ---------------------------------------

$I->amOnModulePage('Staff', 'coverage_view_edit.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Edit Coverage');

$I->fillField('notesStatus', 'Updated coverage notes from view');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------

$I->amOnModulePage('Staff', 'coverage_manage_delete.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->click('Delete');
$I->seeSuccessMessage();

// Restore original settings ---------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
