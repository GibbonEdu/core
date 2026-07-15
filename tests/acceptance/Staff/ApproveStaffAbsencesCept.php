<?php
/**
 * @covers modules/Staff/absences_approval.php
 * @covers modules/Staff/absences_approval_action.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('approve staff absences');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_approval.php');
$I->seeBreadcrumb('Approve Staff Absences');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Create test absence requiring approval --------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

// Create an absence type that requires approval
$gibbonStaffAbsenceTypeID = $I->haveInDatabase('gibbonStaffAbsenceType', [
    'name' => 'Test Approval Type',
    'nameShort' => 'TAT',
    'requiresApproval' => 'Y',
    'active' => 'Y',
    'reasons' => '',
    'sequenceNumber' => 99,
]);

$gibbonStaffAbsenceID = $I->haveInDatabase('gibbonStaffAbsence', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonStaffAbsenceTypeID' => $gibbonStaffAbsenceTypeID,
    'gibbonSchoolYearID' => $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']),
    'gibbonPersonIDApproval' => $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']),
    'gibbonPersonIDCreator' => $gibbonPersonID,
    'status' => 'Pending Approval',
    'coverageRequired' => 'N',
    'comment' => 'Test absence for approval',
]);

$gibbonStaffAbsenceDateID = $I->haveInDatabase('gibbonStaffAbsenceDate', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'date' => date('Y-m-d', strtotime('+1 day')),
    'allDay' => 'Y',
]);

// Test Approval Action --------------------------------

$I->amOnModulePage('Staff', 'absences_approval_action.php', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'status' => 'Approved'
]);

// Check page loads (may show access denied if approval person doesn't match, but shouldn't crash)
$I->dontSeeErrors();

$I->selectOption('status', 'Approved');
$I->click('Submit');
$I->seeSuccessMessage();

// Clean up test data ----------------------------------

$I->amOnModulePage('Staff', 'absences_manage_delete.php', ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID]);
$I->click('Delete');
$I->seeSuccessMessage();
