<?php
/**
 * @covers modules/Staff/absences_manage.php
 * @covers modules/Staff/absences_manage_edit.php
 * @covers modules/Staff/absences_manage_edit_edit.php
 * @covers modules/Staff/absences_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff absences with edit and delete operations');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'absences_manage.php');
$I->seeBreadcrumb('Manage Staff Absences');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Create test absence data ----------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonStaffAbsenceTypeID = $I->grabFromDatabase('gibbonStaffAbsenceType', 'gibbonStaffAbsenceTypeID', []);

$gibbonStaffAbsenceID = $I->haveInDatabase('gibbonStaffAbsence', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonStaffAbsenceTypeID' => $gibbonStaffAbsenceTypeID,
    'gibbonSchoolYearID' => $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']),
    'gibbonPersonIDCreator' => $gibbonPersonID,
    'status' => 'Approved',
    'coverageRequired' => 'N',
    'comment' => 'Test absence',
]);

$gibbonStaffAbsenceDateID = $I->haveInDatabase('gibbonStaffAbsenceDate', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'date' => date('Y-m-d'),
    'allDay' => 'Y',
]);

// Edit Absence ----------------------------------------

$I->amOnModulePage('Staff', 'absences_manage_edit.php', ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID]);
$I->seeBreadcrumb('Edit Absence');

$I->seeInField('comment', 'Test absence');

$I->fillField('comment', 'Updated test absence');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Edit Nested Date ------------------------------------

$I->amOnModulePage('Staff', 'absences_manage_edit_edit.php', [
    'gibbonStaffAbsenceID' => $gibbonStaffAbsenceID,
    'gibbonStaffAbsenceDateID' => $gibbonStaffAbsenceDateID
]);
$I->seeBreadcrumb('Edit');

$I->uncheckOption('allDay');
$I->fillField('timeStart', '09:00');
$I->fillField('timeEnd', '12:00');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Delete Absence --------------------------------------

$I->amOnModulePage('Staff', 'absences_manage_delete.php', ['gibbonStaffAbsenceID' => $gibbonStaffAbsenceID]);

$I->click('Delete');
$I->seeSuccessMessage();
