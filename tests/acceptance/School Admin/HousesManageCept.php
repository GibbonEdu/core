<?php
/**
 * @covers modules/School Admin/house_manage.php
 * @covers modules/School Admin/house_manage_add.php
 * @covers modules/School Admin/house_manage_edit.php
 * @covers modules/School Admin/house_manage_delete.php
 * @covers modules/School Admin/house_manage_assign.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete houses');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'house_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add House');

$addFormValues = array(
    'name'      => 'Test House',
    'nameShort' => 'TH1',
);

$I->attachFile('file1', 'attachment.jpg');
$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonHouseID = $I->grabEditIDFromURL();
$file = $I->grabFromDatabase('gibbonHouse', 'logo', ['gibbonHouseID' => $gibbonHouseID]);
$I->assertNotEmpty($file);

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_edit.php', array('gibbonHouseID' => $gibbonHouseID));
$I->seeBreadcrumb('Edit House');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'      => 'House Test',
    'nameShort' => 'TH2',
);

$I->fillField('logo', '');
$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonHouseID = $I->grabValueFromURL('gibbonHouseID');
$I->seeInDatabase('gibbonHouse', ['gibbonHouseID' => $gibbonHouseID, 'logo' => '']);

// Edit - File Upload ------------------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_edit.php', array('gibbonHouseID' => $gibbonHouseID));

$I->attachFile('file1', 'attachment2.png');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$file2 = $I->grabFromDatabase('gibbonHouse', 'logo', ['gibbonHouseID' => $gibbonHouseID]);
$I->assertNotEmpty($file2);

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_delete.php', array('gibbonHouseID' => $gibbonHouseID));

$I->click('Delete');
$I->seeSuccessMessage();

// Test Assign Houses ---------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_assign.php');
$I->seeBreadcrumb('Assign Houses');

$I->selectFromDropdown('gibbonYearGroupIDList', 1);
$I->selectFromDropdown('gibbonHouseIDList', 1);

$assignFormValues = array(
    'balanceGender'    => 'Y',
    'balanceYearGroup' => 'Y',
    'overwrite'        => 'N',
);

$I->submitForm('#content form', $assignFormValues, 'Submit');
$I->seeSuccessMessage();

