<?php
/**
 * @covers modules/Attendance/attendance_take_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('take attendance by form group');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_take_byFormGroup.php');
$I->seeBreadcrumb('Take Attendance by Form Group');

// TODO: Add test for taking attendance
// Requires: form groups with students enrolled
