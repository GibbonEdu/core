<?php
/**
 * @covers modules/Students/medicalForm_manage_add.php
 * @covers modules/Students/medicalForm_manage_addProcess.php
 * @covers src/Forms/CustomFieldHandler.php
 * @covers src/Forms/Input/CustomField.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test file upload on a custom field for a medical form');
$I->loginAsAdmin();

// Create a custom field of type 'file' for Medical Form context
$gibbonCustomFieldID = $I->haveInDatabase('gibbonCustomField', [
    'context'               => 'Medical Form',
    'name'                  => 'Test Medical File Upload',
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

// Find a person to use for the medical form test
// Use a student, and delete any existing medical form so we can add a fresh one
$testPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', [
    'status' => 'Full',
    'gibbonRoleIDPrimary' => '003',
]);
$I->deleteFromDatabase('gibbonPersonMedical', ['gibbonPersonID' => $testPersonID]);

// Add a medical form with the custom field file upload
$I->amOnModulePage('Students', 'medicalForm_manage_add.php');
$I->seeBreadcrumb('Add Medical Form');

$I->submitForm('#content form', [
    'gibbonPersonID' => str_pad($testPersonID, 10, '0', STR_PAD_LEFT),
], 'Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalID = $I->grabEditIDFromURL();

// Edit the medical form to upload the custom field file
$I->amOnModulePage('Students', 'medicalForm_manage_edit.php', ['gibbonPersonMedicalID' => $gibbonPersonMedicalID]);
$I->seeBreadcrumb('Edit Medical Form');

// Verify the custom field file input is present
$I->seeElement('input[type="file"][name="'.$fieldName.'"]');

$I->attachFile('input[type="file"][name="'.$fieldName.'"]', 'attachment.txt');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Verify the uploaded file path is stored in the fields JSON column
$fieldsJson = $I->grabFromDatabase('gibbonPersonMedical', 'fields', ['gibbonPersonMedicalID' => $gibbonPersonMedicalID]);
$fields = json_decode($fieldsJson, true);
$I->assertNotEmpty($fields[$fieldID] ?? '', 'Custom field file upload path should be stored in fields JSON');

$file = $fields[$fieldID];

// Cleanup
$I->amOnModulePage('Students', 'medicalForm_manage_delete.php', ['gibbonPersonMedicalID' => $gibbonPersonMedicalID]);
$I->click('Delete');
$I->seeSuccessMessage();

$I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);

if (!empty($file)) {
    $I->deleteFile('../'.$file);
}
