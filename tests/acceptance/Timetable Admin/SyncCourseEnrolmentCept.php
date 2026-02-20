<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('check sync course enrolment');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable Admin', 'courseEnrolment_sync.php');
$I->seeBreadcrumb('Sync Course Enrolment');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
