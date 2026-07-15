<?php
/**
 * @covers modules/Timetable Admin/report_classEnrolment_byFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Class Enrolment by Form Group');
$I->loginAsAdmin();

$I->amOnModulePage('Timetable Admin', 'report_classEnrolment_byFormGroup.php');
$I->seeBreadcrumb('Class Enrolment by Form Group');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
