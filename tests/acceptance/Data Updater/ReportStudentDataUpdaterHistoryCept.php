<?php
/**
 * @covers modules/Data Updater/report_student_dataUpdaterHistory.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Student Data Updater History report');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'report_student_dataUpdaterHistory.php');
$I->seeBreadcrumb('Student Data Updater History');
$I->see('This report allows a user to select a range of students');
