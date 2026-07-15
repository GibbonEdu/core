<?php
/**
 * @covers modules/Activities/report_activityEnrollmentSummary.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view activity enrollment summary report');
$I->loginAsAdmin();
$I->amOnModulePage('Activities', 'report_activityEnrollmentSummary.php');
$I->seeBreadcrumb('Activity Enrolment Summary');

// Test Print button
$I->click('Print');
