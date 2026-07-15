<?php
/**
 * @covers modules/Attendance/attendance_take_adHoc.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('take ad hoc attendance');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_take_adHoc.php');
$I->seeBreadcrumb('Take Ad Hoc Attendance');

// TODO: Add test for taking ad hoc attendance
// Requires: students enrolled in current school year
