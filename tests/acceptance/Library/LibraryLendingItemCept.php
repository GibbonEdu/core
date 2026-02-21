<?php
/**
 * @covers modules/Library/library_lending_item.php
 * @covers modules/Library/library_lending_item_edit.php
 * @covers modules/Library/library_lending_item_renew.php
 * @covers modules/Library/library_lending_item_return.php
 * @covers modules/Library/library_lending_item_signout.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view and manage library lending item actions');
$I->loginAsAdmin();

// Create a test library item first
$I->amOnModulePage('Library', 'library_manage_catalog.php');
$I->seeBreadcrumb('Manage Catalog');

$I->click('Add', 'a');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonLibraryTypeID', 1);
$I->selectFromDropdown('gibbonSpaceID', 1);

$formValues = array(
    'name' => 'Test Lending Item',
    'producer' => 'Test Producer',
    'idCheck' => 'LEND' . time(),
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

// View Item Page -----------------------------------------
$I->amOnModulePage('Library', 'library_lending_item.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID
));
$I->seeBreadcrumb('View Item');
$I->dontSeeErrors();

// Test Sign Out Action -----------------------------------
$I->click('Sign Out');
$I->seeBreadcrumb('Sign Out');
$I->dontSeeErrors();

// Sign out the item to a student
$I->selectFromDropdown('gibbonPersonIDStatusResponsible', 1);

$signoutValues = array(
    'status' => 'On Loan',
    'returnExpected' => date('Y-m-d', strtotime('+7 days')),
    'notifyParents' => 'N',
);

$I->submitForm('#content form', $signoutValues, 'Submit');
$I->seeSuccessMessage();

// Get the event ID from the database
$gibbonLibraryItemEventID = $I->grabFromDatabase('gibbonLibraryItemEvent', 'gibbonLibraryItemEventID', [
    'gibbonLibraryItemID' => $gibbonLibraryItemID,
    'status' => 'On Loan'
]);

// Test Edit Action ---------------------------------------
$I->amOnModulePage('Library', 'library_lending_item_edit.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID,
    'gibbonLibraryItemEventID' => $gibbonLibraryItemEventID
));
$I->seeBreadcrumb('Edit');
$I->dontSeeErrors();

// Update the return date
$editValues = array(
    'status' => 'On Loan',
    'returnExpected' => date('Y-m-d', strtotime('+14 days')),
);

$I->submitForm('#content form', $editValues, 'Submit');
$I->seeSuccessMessage();

// Test Renew Action --------------------------------------
$I->amOnModulePage('Library', 'library_lending_item_renew.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID,
    'gibbonLibraryItemEventID' => $gibbonLibraryItemEventID
));
$I->seeBreadcrumb('Renew');
$I->dontSeeErrors();

$renewValues = array(
    'returnExpected' => date('Y-m-d', strtotime('+21 days')),
);

$I->submitForm('#content form', $renewValues, 'Submit');
$I->seeSuccessMessage();

// Test Return Action -------------------------------------
$I->amOnModulePage('Library', 'library_lending_item_return.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID,
    'gibbonLibraryItemEventID' => $gibbonLibraryItemEventID
));
$I->seeBreadcrumb('Return');
$I->dontSeeErrors();

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Clean up - Delete the test item ------------------------
$I->amOnModulePage('Library', 'library_manage_catalog_delete.php', array(
    'gibbonLibraryItemID' => $gibbonLibraryItemID
));

$I->click('Delete');
$I->seeSuccessMessage();
