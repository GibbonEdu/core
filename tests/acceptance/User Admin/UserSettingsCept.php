<?php 
/**
 * @covers modules/User Admin/userSettings.php
 * @covers modules/User Admin/userSettings_usernameFormat_add.php
 * @covers modules/User Admin/userSettings_usernameFormat_edit.php
 * @covers modules/User Admin/userSettings_usernameFormat_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage user settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'userSettings.php');

// Add Username Format -----------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Username Format');

$I->selectFromDropdown('gibbonRoleIDList', 1);

$addFormValues = [
    'format' => '[preferredName:1][surname]',
    'isDefault' => 'N',
    'isNumeric' => 'N',
];

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonUsernameFormatID = $I->grabEditIDFromURL();

// Edit Username Format ----------------------------------
$I->amOnModulePage('User Admin', 'userSettings_usernameFormat_edit.php', [
    'gibbonUsernameFormatID' => $gibbonUsernameFormatID
]);
$I->seeBreadcrumb('Edit Username Format');

$I->seeInFormFields('#content form', [
    'format' => '[preferredName:1][surname]',
]);

$editFormValues = [
    'format' => '[firstName][surname]',
    'isDefault' => 'N',
    'isNumeric' => 'N',
];

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Username Format --------------------------------
$I->amOnModulePage('User Admin', 'userSettings_usernameFormat_delete.php', [
    'gibbonUsernameFormatID' => $gibbonUsernameFormatID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Test Settings Page (original test) -------------------
$I->amOnModulePage('User Admin', 'userSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'nationality'        => 'Nationality 1,Nationality 2,Nationality 3',
    'ethnicity'          => 'Ethnicity 1,Ethnicity 2',
    'religions'          => 'Religion 1,Religion 3,Religion 3,Religion 4',
    'departureReasons'   => 'Reason 1,Reason 2,Reason 3',
    'privacy'            => 'Y',
    'privacyBlurb'       => 'Privacy Blurb Test',
    'privacyOptions'     => 'Privacy 1,Privacy 2,Privacy 3',
    'privacyOptionVisibility' => 'Y',
    'personalBackground' => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
