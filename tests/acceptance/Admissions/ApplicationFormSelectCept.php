<?php
/**
 * @covers modules/Admissions/applicationFormSelect.php
 * @covers modules/Admissions/applicationForm.php
 * @covers modules/Admissions/applicationForm_payFee.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Admissions Welcome and Application Form');

$I->updateInDatabase('gibbonForm', ['active' => 'Y', 'public' => 'Y'], ['name' => 'Sample Application Form']);

// Basic Check -----------------------------------------
$I->amOnModulePage('Admissions', 'applicationFormSelect.php');

$I->see('Sample Application Form');
$I->dontSeeErrors();

$I->fillField('admissionsLoginEmail', 'testnew' . time() . '@example.com');
$I->click('Next');

$I->seeBreadcrumb('Application Form');
$I->dontSeeErrors();

// Test Application Form access link ----
$gibbonFormID = $I->grabValueFromURL('gibbonFormID');
$accessID = $I->grabValueFromURL('accessID');

$gibbonAdmissionsAccountID = $I->grabFromDatabase('gibbonAdmissionsAccount', 'gibbonAdmissionsAccountID', [
    'accessID' => $accessID
]);

$accessToken = $I->grabFromDatabase('gibbonAdmissionsAccount', 'accessToken', [
    'accessID' => $accessID
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
    'tok' => $accessToken,
    'gibbonFormID' => $gibbonFormID,
    'identifier' => $identifier,
]);
$I->seeBreadcrumb('Application Fee');
$I->dontSeeErrors();


$I->deleteFromDatabase('gibbonAdmissionsAccount', ['gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID]);
$I->deleteFromDatabase('gibbonAdmissionsApplication', ['gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID]);

$I->updateInDatabase('gibbonForm', ['active' => 'N', 'public' => 'N'], ['name' => 'Sample Application Form']);
