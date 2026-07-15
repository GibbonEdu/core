<?php
/**
 * @covers modules/Attendance/report_summary_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view attendance summary by date report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_summary_byDate.php');
$I->seeBreadcrumb('Attendance Summary by Date');
