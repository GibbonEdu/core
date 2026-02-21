<?php
/**
 * @covers modules/Library/library_manage_shelves_edit_items_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('delete items from a library shelf');
$I->loginAsAdmin();

// Create a test library item first
$I->amOnModulePage('Library', 'library_manage_catalog.php');
$I->seeBreadcrumb('Manage Catalog');

$I->click('Add', 'a');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonLibraryTypeID', 1);
$I->selectFromDropdown('gibbonSpaceID', 1);

$formValues = array(
    'name' => 'Test Shelf Item',
    'producer' => 'Test Producer',
    'idCheck' => 'SHELF' . time(),
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

// Create a manual shelf directly in the database
$gibbonLibraryShelfID = $I->haveInDatabase('gibbonLibraryShelf', [
    'name' => 'Test Manual Shelf',
    'active' => 'Y',
    'shuffle' => 'N',
    'type' => 'Manual',
    'field' => '',
    'sequenceNumber' => 1,
]);

// Add the item to the shelf directly in the database
$gibbonLibraryShelfItemID = $I->haveInDatabase('gibbonLibraryShelfItem', [
    'gibbonLibraryShelfID' => $gibbonLibraryShelfID,
    'gibbonLibraryItemID' => $gibbonLibraryItemID,
]);

// Navigate to edit shelf page to see the item
$I->amOnModulePage('Library', 'library_manage_shelves_edit.php', array(
    'gibbonLibraryShelfID' => $gibbonLibraryShelfID
));
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Test Delete Item Action --------------------------------
$I->amOnModulePage('Library', 'library_manage_shelves_edit_items_delete.php', array(
    'gibbonLibraryShelfID' => $gibbonLibraryShelfID,
    'gibbonLibraryShelfItemID' => $gibbonLibraryShelfItemID
));

$I->click('Delete');
$I->seeSuccessMessage();

// Clean up - Delete the catalog item --------------------
$I->amOnModulePage('Library', 'library_manage_catalog_delete.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID
));

$I->click('Delete');
$I->seeSuccessMessage();
