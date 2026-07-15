<?php
/**
 * @covers modules/Attendance/attendance_future_byPerson.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('set future absence');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_future_byPerson.php');
$I->seeBreadcrumb('Set Future Absence');

// TODO: Add test for setting future absence
// Requires: students enrolled in current school year
