<?php
/**
 * @covers modules/System Admin/formBuilder_page_edit_field_add.php
 * @covers modules/System Admin/formBuilder_page_edit_field_edit.php
 * @covers modules/System Admin/formBuilder_page_edit_field_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Form Builder Page Fields');
$I->loginAsAdmin();

// Get an existing form and page
$gibbonFormID = $I->grabFromDatabase('gibbonForm', 'gibbonFormID', []);
$gibbonFormPageID = $I->grabFromDatabase('gibbonFormPage', 'gibbonFormPageID', ['gibbonFormID' => $gibbonFormID]);

// Check Add Field Page --------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_edit_field_add.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID,
    'fieldGroup' => 'GenericFields'
]);
$I->dontSeeErrors();

// Get an existing field
$gibbonFormFieldID = $I->grabFromDatabase('gibbonFormField', 'gibbonFormFieldID', ['gibbonFormPageID' => $gibbonFormPageID]);

// Check Edit Field Page -------------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_edit_field_edit.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID,
    'gibbonFormFieldID' => $gibbonFormFieldID
]);
$I->seeBreadcrumb('Edit Field');
$I->dontSeeErrors();

// Check Delete Field Page -----------------------------
$I->amOnModulePage('System Admin', 'formBuilder_page_edit_field_delete.php', [
    'gibbonFormID' => $gibbonFormID,
    'gibbonFormPageID' => $gibbonFormPageID,
    'gibbonFormFieldID' => $gibbonFormFieldID
]);
$I->dontSeeErrors();
