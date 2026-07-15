<?php
/**
 * @covers modules/Activities/report_attendance_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Attendance by Date');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_attendance_byDate.php');
$I->seeBreadcrumb('Activity Attendance by Date');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
