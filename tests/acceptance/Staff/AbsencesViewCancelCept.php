<?php
/**
 * @covers modules/Staff/absences_view_cancel.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Cancel Absence');
$I->loginAsAdmin();

// Create test data for absence
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
    'coverageRequired' => 'N',
    'gibbonPersonIDCreator' => $gibbonPersonID,
]);

// Create absence date
$I->haveInDatabase('gibbonStaffAbsenceDate', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'date' => $futureDate,
    'allDay' => 'Y',
]);

$I->amOnModulePage('Staff', 'absences_view_cancel.php', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
]);
$I->seeBreadcrumb('Cancel Absence');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
