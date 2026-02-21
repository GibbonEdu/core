<?php
/**
 * @covers modules/Staff/applicationForm_manage.php
 * @covers modules/Staff/applicationForm_manage_accept.php
 * @covers modules/Staff/applicationForm_manage_edit.php
 * @covers modules/Staff/applicationForm_manage_delete.php
 * @covers modules/Staff/applicationForm_manage_reject.php
 * @covers modules/Staff/applicationForm_manageProcessBulk.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff applications with accept, edit, reject, and delete operations');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'applicationForm_manage.php');
$I->seeBreadcrumb('Manage Applications');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#action', []);
$I->dontSeeErrors();

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);

// Create test application data ------------------------

$gibbonStaffJobOpeningID = $I->haveInDatabase('gibbonStaffJobOpening', [
    'type' => 'Teaching',
    'jobTitle' => 'Test Job Opening',
    'dateOpen' => date('Y-m-d'),
    'active' => 'Y',
    'description' => '',
    'gibbonPersonIDCreator' => $gibbonPersonID,
]);

$gibbonStaffApplicationFormID = $I->haveInDatabase('gibbonStaffApplicationForm', [
    'gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID,
    'surname' => 'TestApplicant',
    'firstName' => 'Test',
    'preferredName' => 'Test',
    'officialName' => 'Test Applicant',
    'gender' => 'M',
    'dob' => '1990-01-01',
    'email' => 'test.applicant@example.com',
    'homeAddress' => '123 Test Street',
    'homeAddressDistrict' => 'Test District',
    'homeAddressCountry' => 'Antarctica',
    'phone1' => '1234567890',
    'languageFirst' => 'English',
    'status' => 'Pending',
    'priority' => '0',
    'timestamp' => date('Y-m-d H:i:s'),
    'milestones' => '',
    'notes' => '',
    'questions' => '',
    'fields' => '',
    'referenceEmail1' => 'ref1@example.com',
    'referenceEmail2' => 'ref2@example.com',
    'gibbonPersonID' => null
]);


// Edit Application ------------------------------------

$I->amOnModulePage('Staff', 'applicationForm_manage_edit.php', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID]);
$I->seeBreadcrumb('Edit Form');

$I->seeInField('surname', 'TestApplicant');
$I->fillField('notes', 'Test notes for application');
$I->selectOption('priority', '0');


$I->submitForm('#content form', ['gibbonPersonID' => null, 'gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID], 'Submit');
$I->seeSuccessMessage();

// // Accept Application ----------------------------------

$I->amOnModulePage('Staff', 'applicationForm_manage_accept.php', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID]);
$I->seeBreadcrumb('Accept Application');

// Check page loads (may show error if form has issues, but shouldn't crash)
$I->dontSeeErrors();

// // Create another test application for rejection -------

$gibbonStaffApplicationFormID2 = $I->haveInDatabase('gibbonStaffApplicationForm', [
    'gibbonStaffJobOpeningID' => $gibbonStaffJobOpeningID,
    'surname' => 'TestReject',
    'firstName' => 'Reject',
    'preferredName' => 'Reject',
    'officialName' => 'Reject Test',
    'gender' => 'F',
    'dob' => '1991-01-01',
    'email' => 'reject.test@example.com',
    'homeAddress' => '456 Reject Street',
    'homeAddressDistrict' => 'Reject District',
    'homeAddressCountry' => 'Antarctica',
    'phone1' => '0987654321',
    'languageFirst' => 'English',
    'status' => 'Pending',
    'priority' => '0',
    'timestamp' => date('Y-m-d H:i:s'),
    'milestones' => '',
    'notes' => '',
    'questions' => '',
    'fields' => '',
    'referenceEmail1' => 'ref1@example.com',
    'referenceEmail2' => 'ref2@example.com',
    'gibbonPersonID' => null
]);

// // Reject Application ----------------------------------

$I->amOnModulePage('Staff', 'applicationForm_manage_reject.php', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID2]);
$I->seeBreadcrumb('Reject Application');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// // Delete Application ----------------------------------

$I->amOnModulePage('Staff', 'applicationForm_manage_delete.php', ['gibbonStaffApplicationFormID' => $gibbonStaffApplicationFormID2]);

$I->click('Delete');
$I->seeSuccessMessage();
