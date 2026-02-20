<?php
/**
 * @covers modules/Attendance/report_consecutiveAbsences.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view consecutive absences report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_consecutiveAbsences.php');
$I->seeBreadcrumb('Consecutive Absences');
