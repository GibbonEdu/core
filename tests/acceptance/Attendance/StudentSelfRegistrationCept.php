<?php
/**
 * @covers modules/Attendance/attendance_studentSelfRegister.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check student self registration');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'attendance_studentSelfRegister.php');
$I->seeBreadcrumb('Student Self Registration');
