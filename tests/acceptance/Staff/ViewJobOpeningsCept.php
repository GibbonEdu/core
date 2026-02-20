<?php
/**
 * @covers modules/Staff/applicationForm_jobOpenings_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View job openings');
$I->loginAsAdmin();

// Get an existing job opening ID
$gibbonStaffJobOpeningID = $I->grabFromDatabase('gibbonStaffJobOpening', 'gibbonStaffJobOpeningID', []);

$I->amOnModulePage('Staff', 'applicationForm_jobOpenings_view.php', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
$I->seeBreadcrumb('Job Openings');
