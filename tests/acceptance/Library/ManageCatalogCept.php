<?php
/**
 * @covers modules/Library/library_manage_catalog.php
 * @covers modules/Library/library_manage_catalog_add.php
 * @covers modules/Library/library_manage_catalog_edit.php
 * @covers modules/Library/library_manage_catalog_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a catalog item');
$I->loginAsAdmin();
$I->amOnModulePage('Library', 'library_manage_catalog.php');
$I->seeBreadcrumb('Manage Catalog');

// Add ------------------------------------------------
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonLibraryTypeID', 1);
$I->selectFromDropdown('gibbonSpaceID', 1);

$formValues = array(
    'name' => 'Test Catalog Item',
    'producer' => 'Test Producer',
    'idCheck' => 'TEST001',
    'vendor' => 'Test Vendor',
    'purchaseDate' => date('Y-m-d'),
    'invoiceNumber' => 'INV001',
    'borrowable' => 'Y',
    'status' => 'Available',
    'replacement' => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonLibraryItemID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Library', 'library_manage_catalog_edit.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID
));
$I->seeBreadcrumb('Edit');

$I->seeInFormFields('#content form', array(
    'name' => 'Test Catalog Item',
));

$formValues = array(
    'name' => 'Updated Catalog Item',
    'producer' => 'Updated Producer',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('Library', 'library_manage_catalog_delete.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID
));

$I->click('Delete');
$I->seeSuccessMessage();
