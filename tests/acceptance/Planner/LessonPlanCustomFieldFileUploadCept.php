<?php
/**
 * @covers modules/Planner/planner_edit.php
 * @covers modules/Planner/planner_editProcess.php
 * @covers src/Forms/CustomFieldHandler.php
 * @covers src/Forms/Input/CustomField.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('test file upload on a custom field for a lesson plan');
$I->loginAsAdmin();

// Create a custom field of type 'file' for Lesson Plan context
$gibbonCustomFieldID = $I->haveInDatabase('gibbonCustomField', [
    'context'               => 'Lesson Plan',
    'name'                  => 'Test Lesson Plan File Upload',
    'active'                => 'Y',
    'description'           => 'Acceptance test file upload custom field',
    'type'                  => 'file',
    'options'               => '',
    'required'              => 'N',
    'hidden'                => 'N',
    'heading'               => 'Basic Information',
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

// Grab an existing planner entry to edit
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonPlannerEntryID', []);
$gibbonCourseClassID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonCourseClassID', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
]);

if (!$gibbonPlannerEntryID) {
    // No planner entry exists, clean up and skip
    $I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);
    $I->comment('Skipping: No planner entry found');
    return;
}

$I->amOnModulePage('Planner', 'planner_edit.php', [
    'viewBy'               => 'class',
    'gibbonCourseClassID'  => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
]);
$I->seeBreadcrumb('Edit');

// Verify the custom field file input is present
$I->seeElement('input[type="file"][name="'.$fieldName.'"]');

// Upload a file to the custom field
$I->attachFile('input[type="file"][name="'.$fieldName.'"]', 'attachment.txt');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Verify the uploaded file path is stored in the fields JSON column
$fieldsJson = $I->grabFromDatabase('gibbonPlannerEntry', 'fields', ['gibbonPlannerEntryID' => $gibbonPlannerEntryID]);
$fields = json_decode($fieldsJson, true);
$I->assertNotEmpty($fields[$fieldID] ?? '', 'Custom field file upload path should be stored in fields JSON');

$file = $fields[$fieldID];

// Cleanup
$I->deleteFromDatabase('gibbonCustomField', ['gibbonCustomFieldID' => $gibbonCustomFieldID]);

// Clear the fields value back to empty
$I->updateInDatabase('gibbonPlannerEntry', ['fields' => ''], ['gibbonPlannerEntryID' => $gibbonPlannerEntryID]);

if (!empty($file)) {
    $I->deleteFile('../'.$file);
}
