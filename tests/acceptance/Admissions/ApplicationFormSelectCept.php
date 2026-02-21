<?php
/**
 * @covers modules/Admissions/applicationFormSelect.php
 * @covers modules/Admissions/applicationForm.php
 * @covers modules/Admissions/applicationForm_payFee.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Admissions Welcome and Application Form');
$I->loginAsAdmin();

$I->amOnModulePage('Admissions', 'applicationFormSelect.php');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Test Application Form (requires accessID and gibbonFormID) ----

// Get an active form
$gibbonFormID = $I->grabFromDatabase('gibbonForm', 'gibbonFormID', [
    'type' => 'Application',
    'active' => 'Y'
]);

// Create a test admissions account
$gibbonAdmissionsAccountID = $I->haveInDatabase('gibbonAdmissionsAccount', [
    'email' => 'testapp' . time() . '@example.com',
    'accessID' => 'TEST' . time(),
    'timestampCreated' => date('Y-m-d H:i:s'),
]);

$accessID = $I->grabFromDatabase('gibbonAdmissionsAccount', 'accessID', [
    'gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID
]);

// Test Application Form page
$I->amOnModulePage('Admissions', 'applicationForm.php', [
    'accessID' => $accessID,
    'gibbonFormID' => $gibbonFormID
]);
$I->seeBreadcrumb('Application Form');
$I->dontSeeErrors();

// Test Application Pay Fee page (requires identifier)
// Create a test application
$identifier = 'TEST' . time();
$gibbonAdmissionsApplicationID = $I->haveInDatabase('gibbonAdmissionsApplication', [
    'gibbonFormID' => $gibbonFormID,
    'foreignTable' => 'gibbonAdmissionsAccount',
    'foreignTableID' => $gibbonAdmissionsAccountID,
    'identifier' => $identifier,
    'status' => 'Incomplete',
    'timestampCreated' => date('Y-m-d H:i:s'),
]);

$I->amOnModulePage('Admissions', 'applicationForm_payFee.php', [
    'accessID' => $accessID,
    'gibbonFormID' => $gibbonFormID,
    'identifier' => $identifier
]);
$I->seeBreadcrumb('Application Fee');
$I->dontSeeErrors();

// Cleanup
$I->amOnModulePage('Admissions', 'admissions_manage_delete.php', [
    'gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID
]);
$I->click('Delete');
$I->seeSuccessMessage();
