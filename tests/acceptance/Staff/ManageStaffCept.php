<?php
/**
 * @covers modules/Staff/staff_manage.php
 * @covers modules/Staff/staff_manage_add.php
 * @covers modules/Staff/staff_manage_edit.php
 * @covers modules/Staff/staff_manage_delete.php
 * @covers modules/Staff/staff_manage_edit_contract_add.php
 * @covers modules/Staff/staff_manage_edit_contract_edit.php
 * @covers modules/Staff/staff_manage_edit_facility_add.php
 * @covers modules/Staff/staff_manage_edit_facility_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff with full CRUD and nested contract/facility management');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'staff_manage.php');
$I->seeBreadcrumb('Manage Staff');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Add Staff -------------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Staff');

$I->selectFromDropdown('gibbonPersonID', 1);
$I->selectFromDropdown('type', 1);
$I->fillField('jobTitle', 'Test Teacher');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$gibbonStaffID = $I->grabEditIDFromURL();


// Edit Staff ------------------------------------------

$I->amOnModulePage('Staff', 'staff_manage_edit.php', ['gibbonStaffID' => $gibbonStaffID]);
$I->seeBreadcrumb('Edit Staff');

$I->seeInField('jobTitle', 'Test Teacher');
$I->fillField('jobTitle', 'Updated Test Teacher');

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Add Contract ----------------------------------------

$I->amOnModulePage('Staff', 'staff_manage_edit_contract_add.php', ['gibbonStaffID' => $gibbonStaffID]);
$I->seeBreadcrumb('Add Contract');

$I->fillField('title', 'Test Contract');
$I->selectFromDropdown('status', 1);
$I->fillField('dateStart', date('Y-m-d'));

$I->attachFile('file1', 'attachment.txt');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$gibbonStaffContractID = $I->grabEditIDFromURL();
$file = $I->grabFromDatabase('gibbonStaffContract', 'contractUpload', ['gibbonStaffContractID' => $gibbonStaffContractID]);
$I->assertNotEmpty($file);

// Edit Contract ---------------------------------------

$I->amOnModulePage('Staff', 'staff_manage_edit_contract_edit.php', [
    'gibbonStaffID' => $gibbonStaffID,
    'gibbonStaffContractID' => $gibbonStaffContractID
]);
$I->seeBreadcrumb('Edit');

$I->fillField('title', 'Updated Test Contract');
$I->fillField('contractUpload', '');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$gibbonStaffContractID = $I->grabValueFromURL('gibbonStaffContractID');
$I->seeInDatabase('gibbonStaffContract', ['gibbonStaffContractID' => $gibbonStaffContractID, 'contractUpload' => '']);

// Edit Contract - File Upload -------------------------

$I->amOnModulePage('Staff', 'staff_manage_edit_contract_edit.php', [
    'gibbonStaffID' => $gibbonStaffID,
    'gibbonStaffContractID' => $gibbonStaffContractID
]);

$I->fillField('title', 'Updated Test Contract');
$I->attachFile('file1', 'attachment.txt');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$file2 = $I->grabFromDatabase('gibbonStaffContract', 'contractUpload', ['gibbonStaffContractID' => $gibbonStaffContractID]);
$I->assertNotEmpty($file2);

// Add Facility ----------------------------------------

$gibbonPersonID = $I->grabFromDatabase('gibbonStaff', 'gibbonPersonID', ['gibbonStaffID' => $gibbonStaffID]);

$I->amOnModulePage('Staff', 'staff_manage_edit_facility_add.php', ['gibbonStaffID' => $gibbonStaffID, 'gibbonPersonID' => $gibbonPersonID]);

$I->seeBreadcrumb('Add Facility');

$I->selectFromDropdown('gibbonSpaceID', 1);

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$gibbonSpacePersonID = $I->grabEditIDFromURL();

// Delete Facility -------------------------------------

$I->amOnModulePage('Staff', 'staff_manage_edit_facility_delete.php', [
    'gibbonStaffID' => $gibbonStaffID,
    'gibbonSpacePersonID' => $gibbonSpacePersonID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Delete Staff ----------------------------------------

$I->amOnModulePage('Staff', 'staff_manage_delete.php', ['gibbonStaffID' => $gibbonStaffID]);

$I->click('Delete');
$I->seeSuccessMessage();
