<?php
/**
 * @covers modules/Individual Needs/in_edit.php
 * @covers modules/Individual Needs/in_editProcess.php
 * @covers src/Forms/CustomFieldHandler.php
 * @covers src/Forms/Input/CustomField.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test file upload on a custom field for an individual needs record');
$I->loginAsAdmin();

// Create a custom field of type 'file' for Individual Needs context
$gibbonCustomFieldID = $I->haveInDatabase('gibbonCustomField', [
    'context'               => 'Individual Needs',
    'name'                  => 'Test IN File Upload',
    'active'                => 'Y',
    'description'           => 'Acceptance test file upload custom field',
    'type'                  => 'file',
    'options'               => '',
    'required'              => 'N',
    'hidden'                => 'N',
    'heading'               => 'Individual Education Plan',
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

// Get an existing student with enrolment
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', [
    'status' => 'Full',
    'gibbonRoleIDPrimary' => '003',
]);

$I->amOnModulePage('Individual Needs', 'in_edit.php', [
    'gibbonPersonID' => $gibbonPersonID,
]);
$I->seeBreadcrumb('Edit Individual Needs Record');

// Verify the custom field file input is present
$I->seeElement('input[type="file"][name="'.$fieldName.'"]');

// Upload a file to the custom field
$I->attachFile('input[type="file"][name="'.$fieldName.'"]', 'attachment.txt');
$I->submitForm('#individualNeeds', [], 'Submit');
$I->seeSuccessMessage();

// Verify the uploaded file path is stored in the fields JSON column
$fieldsJson = $I->grabFromDatabase('gibbonIN', 'fields', ['gibbonPersonID' => $gibbonPersonID]);
$fields = json_decode($fieldsJson, true);
$I->assertNotEmpty($fields[$fieldID] ?? '', 'Custom field file upload path should be stored in fields JSON');

$file = $fields[$fieldID];

// Cleanup
$I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);

// Clear the fields value back to empty
$I->updateInDatabase('gibbonIN', ['fields' => ''], ['gibbonPersonID' => $gibbonPersonID]);

if (!empty($file)) {
    $I->deleteFile('../'.$file);
}
