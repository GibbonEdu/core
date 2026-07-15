<?php
/**
 * @covers modules/System Admin/formBuilder.php
 * @covers modules/System Admin/formBuilder_add.php
 * @covers modules/System Admin/formBuilder_edit.php
 * @covers modules/System Admin/formBuilder_delete.php
 * @covers modules/System Admin/formBuilder_duplicate.php
 * @covers modules/System Admin/formBuilder_page_add.php
 * @covers modules/System Admin/formBuilder_page_edit.php
 * @covers modules/System Admin/formBuilder_page_delete.php
 * @covers modules/System Admin/formBuilder_page_design.php
 * @covers modules/System Admin/formBuilder_preview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Form Builder pages');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'formBuilder.php');
$I->seeBreadcrumb('Form Builder');

// Check Add Form Page ---------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Form');
$I->dontSeeErrors();

// Get an existing form to test other pages
$gibbonFormID = $I->grabFromDatabase('gibbonForm', 'gibbonFormID', []);

// Check Edit Form Page --------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_edit.php', [
    'gibbonFormID' => $gibbonFormID
]);
$I->seeBreadcrumb('Edit Form');
$I->dontSeeErrors();

// Check Add Page --------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Page');
$I->dontSeeErrors();

// Get an existing page
$gibbonFormPageID = $I->grabFromDatabase('gibbonFormPage', 'gibbonFormPageID', ['gibbonFormID' => $gibbonFormID]);

// Check Edit Page -------------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_edit.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID
]);
$I->seeBreadcrumb('Edit Page');
$I->dontSeeErrors();

// Check Design Page -----------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_design.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID
]);
$I->dontSeeErrors();

// Check Preview Page ----------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_preview.php', [
    'gibbonFormID' => $gibbonFormID
]);
$I->dontSeeErrors();

// Check Duplicate Page --------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_duplicate.php', [
    'gibbonFormID' => $gibbonFormID
]);
$I->seeBreadcrumb('Duplicate');
$I->dontSeeErrors();

// Check Delete Page -----------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_delete.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID
]);
$I->dontSeeErrors();
