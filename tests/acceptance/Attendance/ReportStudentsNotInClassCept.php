<?php
/**
 * @covers modules/Attendance/report_studentsNotInClass_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view students not in class report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_studentsNotInClass_byDate.php');
$I->seeBreadcrumb('Students Not In Class');
