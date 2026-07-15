<?php
/**
 * @covers modules/User Admin/family_manage_edit.php
 * @covers modules/User Admin/family_manage_editProcess.php
 * @covers src/Forms/CustomFieldHandler.php
 * @covers src/Forms/Input/CustomField.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test file upload on a custom field for a family');
$I->loginAsAdmin();

// Create a custom field of type 'file' for Family context
$gibbonCustomFieldID = $I->haveInDatabase('gibbonCustomField', [
    'context'               => 'Family',
    'name'                  => 'Test Family File Upload',
    'active'                => 'Y',
    'description'           => 'Acceptance test file upload custom field',
    'type'                  => 'file',
    'options'               => '',
    'required'              => 'N',
    'hidden'                => 'N',
    'heading'               => 'General Information',
    'sequenceNumber'        => 999,
    'activePersonStudent'   => 0,
    'activePersonStaff'     => 0,
    'activePersonParent'    => 0,
    'activePersonOther'     => 0,
    'activeApplicationForm' => 0,
    'activeDataUpdater'     => 0,
    'activePublicRegistration' => 0,
]);

$fieldID = str_pad($gibbonCustomFieldID, 4, '0', STR_PAD_LEFT);
$fieldName = 'custom'.$fieldID.'File';

// Grab an existing family to edit
$gibbonFamilyID = $I->grabFromDatabase('gibbonFamily', 'gibbonFamilyID', ['status' => 'Married']);

$I->amOnModulePage('User Admin', 'family_manage_edit.php', ['gibbonFamilyID' => $gibbonFamilyID]);
$I->seeBreadcrumb('Edit Family');

// Verify the custom field file input is present
$I->seeElement('input[type="file"][name="'.$fieldName.'"]');

// Upload a file to the custom field
$I->attachFile('input[type="file"][name="'.$fieldName.'"]', 'attachment.txt');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Verify the uploaded file path is stored in the fields JSON column
$fieldsJson = $I->grabFromDatabase('gibbonFamily', 'fields', ['gibbonFamilyID' => $gibbonFamilyID]);
$fields = json_decode($fieldsJson, true);
$I->assertNotEmpty($fields[$fieldID] ?? '', 'Custom field file upload path should be stored in fields JSON');

$file = $fields[$fieldID];

// Cleanup
$I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);

// Clear the fields value back to empty
$I->updateInDatabase('gibbonFamily', ['fields' => ''], ['gibbonFamilyID' => $gibbonFamilyID]);

if (!empty($file)) {
    $I->deleteFile('../'.$file);
}
