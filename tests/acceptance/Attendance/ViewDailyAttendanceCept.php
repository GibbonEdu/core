<?php
/**
 * @covers modules/Attendance/attendance.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view daily attendance');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance.php');
$I->seeBreadcrumb('View Daily Attendance');
