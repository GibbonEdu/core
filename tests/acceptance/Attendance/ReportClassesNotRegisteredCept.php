<?php
/**
 * @covers modules/Attendance/report_courseClassesNotRegistered_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view classes not registered report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_courseClassesNotRegistered_byDate.php');
$I->seeBreadcrumb('Classes Not Registered');
