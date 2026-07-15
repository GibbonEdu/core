<?php
/**
 * @covers modules/Attendance/report_studentHistory.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view student attendance history report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_studentHistory.php');
$I->seeBreadcrumb('Student History');
