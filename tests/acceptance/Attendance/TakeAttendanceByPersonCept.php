<?php
/**
 * @covers modules/Attendance/attendance_take_byPerson.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('take attendance by person');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_take_byPerson.php');
$I->seeBreadcrumb('Take Attendance by Person');

// TODO: Add test for taking attendance
// Requires: students enrolled in current school year
