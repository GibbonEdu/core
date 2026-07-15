<?php
/**
 * @covers modules/Timetable/report_viewAvailableTeachers.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View available teachers');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'report_viewAvailableTeachers.php');
$I->seeBreadcrumb('View Available Teachers');
