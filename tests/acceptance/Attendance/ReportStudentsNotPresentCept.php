<?php
/**
 * @covers modules/Attendance/report_studentsNotPresent_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view students not present report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_studentsNotPresent_byDate.php');
$I->seeBreadcrumb('Students Not Present');
