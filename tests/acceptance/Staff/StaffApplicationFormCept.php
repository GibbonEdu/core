<?php
/**
 * @covers modules/Staff/applicationForm.php
 * @covers modules/Staff/applicationFormProcess.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('submit a staff application form with file upload');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'applicationForm.php');
$I->seeBreadcrumb('Staff Application Form');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Ensure a job opening exists -------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);

$gibbonStaffJobOpeningID = $I->haveInDatabase('gibbonStaffJobOpening', [
    'type'                  => 'Teaching',
    'jobTitle'              => 'Test Upload Job Opening',
    'dateOpen'              => date('Y-m-d'),
    'active'                => 'Y',
    'description'           => 'Test job for file upload coverage',
    'gibbonPersonIDCreator' => $gibbonPersonID,
]);

// Reload the page to see the new job opening
$I->amOnModulePage('Staff', 'applicationForm.php');

// Fill in and submit the form -------------------------

$formValues = [
    'questions' => '<p>I am applying because this is a test.</p>',
    'referenceEmail1' => 'ref1@testingemail.test',
    'referenceEmail2' => 'ref2@testingemail.test',
];

$paddedJobOpeningID = str_pad($gibbonStaffJobOpeningID, 10, '0', STR_PAD_LEFT);
$I->checkOption("input[type=checkbox][value=$paddedJobOpeningID]");
$I->checkOption('agreement');
$I->attachFile('file0', 'attachment.txt');

$I->submitForm('#content form', $formValues, 'Submit');

$I->see('Your application was successfully submitted', '.success');

// Grab the application ID from the success message
$applicationIDs = $I->grabTextFrom('.success b u');

// Verify file was uploaded ----------------------------

$gibbonStaffApplicationFormID = $I->grabFromDatabase('gibbonStaffApplicationForm', 'gibbonStaffApplicationFormID', [
    'gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID,
    'gibbonPersonID'          => $gibbonPersonID,
]);

$filePath = $I->grabFromDatabase('gibbonStaffApplicationFormFile', 'path', [
    'gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID,
    'name'                         => 'Curriculum Vitae',
]);
$I->assertNotEmpty($filePath);

// Cleanup ---------------------------------------------

$I->deleteFromDatabase('gibbonStaffApplicationFormFile', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID]);
$I->deleteFromDatabase('gibbonStaffApplicationForm', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID]);
$I->deleteFromDatabase('gibbonStaffJobOpening', ['gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID]);
$I->deleteFile('../'.$filePath);
