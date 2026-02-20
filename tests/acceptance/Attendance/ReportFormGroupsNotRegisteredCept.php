<?php
/**
 * @covers modules/Attendance/report_formGroupsNotRegistered_byDate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view form groups not registered report');
$I->loginAsAdmin();
$I->amOnModulePage('Attendance', 'report_formGroupsNotRegistered_byDate.php');
$I->seeBreadcrumb('Form Groups Not Registered');
