<?php
/**
 * @covers modules/Staff/applicationForm_jobOpenings_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View job openings');
$I->loginAsAdmin();

// Database Seed  ------------------------------

$gibbonStaffJobOpeningID = $I->haveInDatabase('gibbonStaffJobOpening', [
    'type'        => 'Teaching',
    'jobTitle'    => 'Test Job Title',
    'dateOpen'    => date('Y-m-d'),
    'active'      => 'Y',
    'description' => 'Test job description',
    'gibbonPersonIDCreator' => 1,
]);

// Test View Page  ------------------------------

$I->amOnModulePage('Staff', 'applicationForm_jobOpenings_view.php', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
$I->seeBreadcrumb('Application Form');


// Database Cleanup  ------------------------------

$I->deleteFromDatabase('gibbonStaffJobOpening', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
