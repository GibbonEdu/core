<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check course enrolment by class');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'courseEnrolment_manage.php');
$I->seeBreadcrumb('Course Enrolment by Class');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Test Edit Action (DataTable action) ----------------

$I->click('Edit');
$I->seeInCurrentUrl('courseEnrolment_manage_class_edit.php');
$I->dontSeeErrors();
