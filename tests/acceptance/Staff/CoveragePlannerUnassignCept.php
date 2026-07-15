<?php
/**
 * @covers modules/Staff/coverage_planner_unassign.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check unassign coverage');
$I->loginAsAdmin();

// Create test data for coverage
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonStaffAbsenceTypeID = $I->grabFromDatabase('gibbonStaffAbsenceType', 'gibbonStaffAbsenceTypeID', []);

// Create a future absence
$futureDate = date('Y-m-d', strtotime('+7 days'));
$gibbonStaffAbsenceID = $I->haveInDatabase('gibbonStaffAbsence', [
    'gibbonStaffAbsenceTypeID' => $gibbonStaffAbsenceTypeID,
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'status' => 'Approved',
    'coverageRequired' => 'Y',
    'gibbonPersonIDCreator' => $gibbonPersonID,
]);

// Create absence date
$I->haveInDatabase('gibbonStaffAbsenceDate', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'date' => $futureDate,
    'allDay' => 'Y',
]);

// Create a coverage assignment
$gibbonStaffCoverageID = $I->haveInDatabase('gibbonStaffCoverage', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonPersonID' => $gibbonPersonID,
    'status' => 'Accepted',
    'requestType' => 'Assigned',
    'gibbonPersonIDStatus' => $gibbonPersonID,
    'timestampStatus' => date('Y-m-d H:i:s'),
]);

$I->amOnModulePage('Staff', 'coverage_planner_unassign.php', [
    'gibbonStaffCoverageID' => $gibbonStaffCoverageID,
]);

// Basic Check -----------------------------------------

$I->dontSeeErrors();
