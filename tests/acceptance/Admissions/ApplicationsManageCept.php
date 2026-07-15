<?php
/**
 * @covers modules/Admissions/applications_manage.php
 * @covers modules/Admissions/applications_manage_add.php
 * @covers modules/Admissions/applications_manage_edit.php
 * @covers modules/Admissions/applications_manage_delete.php
 * @covers modules/Admissions/applications_manage_accept.php
 * @covers modules/Admissions/applications_manage_addSelect.php
 * @covers modules/Admissions/applications_manage_reject.php
 * @covers modules/Admissions/applications_manage_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage applications with full CRUD operations');
$I->loginAsAdmin();

$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Admissions', 'applications_manage.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Test Add Select (form selection page) --------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Application');
$I->dontSeeErrors();

// Test View Application -------------------------------

$I->updateInDatabase('gibbonForm', ['active' => 'Y', 'public' => 'Y'], ['name' => 'Sample Application Form']);

// Create test data for viewing
$gibbonFormID = $I->grabFromDatabase('gibbonForm', 'gibbonFormID', [
    'name' => 'Sample Application Form'
]);

$gibbonAdmissionsAccountID = $I->haveInDatabase('gibbonAdmissionsAccount', [
    'email' => 'testview@example.com',
    'accessID' => 'TESTVIEW' . time(),
    'timestampCreated' => date('Y-m-d H:i:s'),
]);

$identifier = 'TESTVIEW' . time();
$gibbonAdmissionsApplicationID = $I->haveInDatabase('gibbonAdmissionsApplication', [
    'gibbonFormID' => $gibbonFormID,
    'foreignTable' => 'gibbonAdmissionsAccount',
    'foreignTableID' => $gibbonAdmissionsAccountID,
    'identifier' => $identifier,
    'status' => 'Pending',
    'timestampCreated' => date('Y-m-d H:i:s'),
    'data' => json_encode(['surname' => 'Test', 'preferredName' => 'Student']),
]);

$I->amOnModulePage('Admissions', 'applications_manage_view.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID
]);
$I->seeBreadcrumb('View & Print Application');
$I->dontSeeErrors();

// Test Reject Application -----------------------------

$I->amOnModulePage('Admissions', 'applications_manage_edit.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID
]);
$I->seeBreadcrumb('Edit Application');
$I->dontSeeErrors();

$I->click('Submit');
$I->seeSuccessMessage();

// Test Reject Application -----------------------------

$I->amOnModulePage('Admissions', 'applications_manage_reject.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID
]);
$I->seeBreadcrumb('Reject Application');
$I->dontSeeErrors();

// Test Accept Application -----------------------------

$I->amOnModulePage('Admissions', 'applications_manage_accept.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID
]);
$I->seeBreadcrumb('Accept Application');
$I->dontSeeErrors();

$I->submitForm('#content form', [], 'Accept');
$I->see('Applicant has been successfully accepted');

// Cleanup ---------------------------------------------

$I->amOnModulePage('Admissions', 'admissions_manage_delete.php', [
    'gibbonAdmissionsAccountID' => $gibbonAdmissionsAccountID
]);
$I->click('Delete');
$I->seeSuccessMessage();
