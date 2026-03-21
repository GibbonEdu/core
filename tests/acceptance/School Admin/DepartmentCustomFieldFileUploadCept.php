<?php
/**
 * @covers modules/School Admin/department_manage_add.php
 * @covers modules/School Admin/department_manage_addProcess.php
 * @covers src/Forms/CustomFieldHandler.php
 * @covers src/Forms/Input/CustomField.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test file upload on a custom field for a department');
$I->loginAsAdmin();

// Create a custom field of type 'file' for Department context
$gibbonCustomFieldID = $I->haveInDatabase('gibbonCustomField', [
    'context'               => 'Department',
    'name'                  => 'Test Department File Upload',
    'active'                => 'Y',
    'description'           => 'Acceptance test file upload custom field',
    'type'                  => 'file',
    'options'               => '',
    'required'              => 'N',
    'hidden'                => 'N',
    'heading'               => 'Basic Details',
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

// Add a department with the custom field file upload
$I->amOnModulePage('School Admin', 'department_manage_add.php');
$I->seeBreadcrumb('Add Department');

// Verify the custom field file input is present
$I->seeElement('input[type="file"][name="'.$fieldName.'"]');

$I->attachFile('input[type="file"][name="'.$fieldName.'"]', 'attachment.txt');
$I->submitForm('#content form', [
    'type'      => 'Learning Area',
    'name'      => 'CF Test Department',
    'nameShort' => 'CFTD',
], 'Submit');
$I->seeSuccessMessage();

$gibbonDepartmentID = $I->grabEditIDFromURL();

// Verify the uploaded file path is stored in the fields JSON column
$fieldsJson = $I->grabFromDatabase('gibbonDepartment', 'fields', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$fields = json_decode($fieldsJson, true);
$I->assertNotEmpty($fields[$fieldID] ?? '', 'Custom field file upload path should be stored in fields JSON');

$file = $fields[$fieldID];

// Cleanup
$I->amOnModulePage('School Admin', 'department_manage_delete.php', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$I->click('Delete');
$I->seeSuccessMessage();

$I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);

if (!empty($file)) {
    $I->deleteFile('../'.$file);
}
