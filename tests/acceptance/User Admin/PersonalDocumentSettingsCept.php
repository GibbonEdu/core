<?php
/**
 * @covers modules/User Admin/personalDocumentSettings.php
 * @covers modules/User Admin/personalDocumentSettings_manage_add.php
 * @covers modules/User Admin/personalDocumentSettings_manage_edit.php
 * @covers modules/User Admin/personalDocumentSettings_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage personal document settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'personalDocumentSettings.php');
$I->seeBreadcrumb('Personal Document Settings');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Personal Document Type');

$I->selectFromDropdown('document', 1);

$addFormValues = [
    'name' => 'Test Document Type',
    'description' => 'Test description',
    'active' => 'Y',
    'required' => 'N',
    'activeDataUpdater' => '1',
    'activeApplicationForm' => '1',
];

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonPersonalDocumentTypeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('User Admin', 'personalDocumentSettings_manage_edit.php', [
    'gibbonPersonalDocumentTypeID' => $gibbonPersonalDocumentTypeID
]);
$I->seeBreadcrumb('Edit Personal Document Type');

$I->seeInFormFields('#content form', [
    'name' => 'Test Document Type',
]);

$editFormValues = [
    'name' => 'Updated Document Type',
    'description' => 'Updated description',
    'active' => 'N',
    'required' => 'Y',
    'activeDataUpdater' => '0',
    'activeApplicationForm' => '0',
];

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('User Admin', 'personalDocumentSettings_manage_delete.php', [
    'gibbonPersonalDocumentTypeID' => $gibbonPersonalDocumentTypeID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Test Settings Page (original test) -------------------
$I->amOnModulePage('User Admin', 'personalDocumentSettings.php');
$I->seeBreadcrumb('Personal Document Settings');

// Grab original values
$originalFormValues = $I->grabAllFormValues('#content form');

// Verify original values are displayed
$I->seeInFormFields('#content form', $originalFormValues);

// Submit modified values (use array_replace to modify only specific fields)
$formValues = array_replace($originalFormValues, array(
    'residencyStatus' => 'Citizen, Permanent Resident, Visa Holder',
));

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('User Admin', 'personalDocumentSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();
