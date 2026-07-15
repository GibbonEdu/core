<?php
/**
 * @covers modules/Activities/report_activityChoices_byStudent.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Activity Choices by Student');
$I->loginAsAdmin();

$I->amOnModulePage('Activities', 'report_activityChoices_byStudent.php');
$I->seeBreadcrumb('Activity Choices By Student');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
