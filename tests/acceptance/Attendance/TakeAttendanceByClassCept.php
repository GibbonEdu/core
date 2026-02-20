<?php
/**
 * @covers modules/Attendance/attendance_take_byCourseClass.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('take attendance by class');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_take_byCourseClass.php');
$I->seeBreadcrumb('Take Attendance by Class');

// TODO: Add test for taking attendance
// Requires: course classes with students enrolled
