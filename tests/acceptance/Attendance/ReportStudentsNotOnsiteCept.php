<?php
/**
 * @covers modules/Attendance/report_studentsNotOnsite_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view students not onsite report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_studentsNotOnsite_byDate.php');
$I->seeBreadcrumb('Students Not Onsite');
