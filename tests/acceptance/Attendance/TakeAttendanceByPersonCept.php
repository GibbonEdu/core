<?php
/**
 * @covers modules/Attendance/attendance_take_byPerson.php
 * @covers modules/Attendance/attendance_take_byPerson_edit.php
 * @covers modules/Attendance/attendance_take_byPerson_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('take, edit and delete attendance by person');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_take_byPerson.php');
$I->seeBreadcrumb('Take Attendance by Person');

// Get a student from the database
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

// Get current date
$currentDate = date('Y-m-d');

// Navigate to take attendance for the student
$I->amOnModulePage('Attendance', 'attendance_take_byPerson.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'currentDate' => $currentDate
]);
$I->seeBreadcrumb('Take Attendance by Person');
$I->dontSeeErrors();

// Create an attendance log entry for testing edit/delete
$gibbonPersonIDTaker = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonAttendanceLogPersonID = $I->haveInDatabase('gibbonAttendanceLogPerson', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonPersonIDTaker' => $gibbonPersonIDTaker,
    'date' => $currentDate,
    'timestampTaken' => date('Y-m-d H:i:s'),
    'direction' => 'In',
    'type' => 'Present',
    'reason' => '',
    'context' => 'Person',
    'comment' => 'Test attendance log',
]);

// Edit ------------------------------------------------
$I->amOnModulePage('Attendance', 'attendance_take_byPerson_edit.php', [
    'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID,
    'gibbonPersonID' => $gibbonPersonID,
    'currentDate' => $currentDate
]);
$I->seeBreadcrumb('Edit Attendance');
$I->dontSeeErrors();

$I->seeInFormFields('#content form', [
    'type' => 'Present',
]);

$I->selectFromDropdown('type', 2);
$I->selectFromDropdown('reason', 1);

$formValues = [
    'comment' => 'Updated attendance comment',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Attendance', 'attendance_take_byPerson_delete.php', [
    'gibbonAttendanceLogPersonID' => $gibbonAttendanceLogPersonID,
    'gibbonPersonID' => $gibbonPersonID,
    'currentDate' => $currentDate
]);

$I->click('Delete');
$I->seeSuccessMessage();
