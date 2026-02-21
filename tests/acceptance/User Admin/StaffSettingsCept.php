<?php 
/**
 * @covers modules/User Admin/staffSettings.php
 * @covers modules/User Admin/staffSettings_manage_add.php
 * @covers modules/User Admin/staffSettings_manage_edit.php
 * @covers modules/User Admin/staffSettings_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff settings');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'staffSettings.php');

// Add Absence Type --------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Absence Type');

$addFormValues = [
    'name' => 'Test Absence Type',
    'nameShort' => 'TEST',
    'active' => 'Y',
    'requiresApproval' => 'Y',
    'reasons' => 'Reason 1, Reason 2, Reason 3',
];

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonStaffAbsenceTypeID = $I->grabEditIDFromURL();

// Edit Absence Type -------------------------------------
$I->amOnModulePage('User Admin', 'staffSettings_manage_edit.php', [
    'gibbonStaffAbsenceTypeID' => $gibbonStaffAbsenceTypeID
]);
$I->seeBreadcrumb('Absence Type');

$I->seeInFormFields('#content form', [
    'name' => 'Test Absence Type',
]);

$editFormValues = [
    'name' => 'Updated Absence Type',
    'nameShort' => 'UPD',
    'active' => 'N',
    'requiresApproval' => 'N',
    'reasons' => 'Updated Reason 1, Updated Reason 2',
];

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Absence Type -----------------------------------
$I->amOnModulePage('User Admin', 'staffSettings_manage_delete.php', [
    'gibbonStaffAbsenceTypeID' => $gibbonStaffAbsenceTypeID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Test Settings Page (original test) -------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'salaryScalePositions'          => '0,1,1,2,3,5,8,13,21,34',
    'responsibilityPosts'           => 'Post 1,Post 2,Post 3',
    'jobOpeningDescriptionTemplate' => '<div>Job Template Test</div>',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
