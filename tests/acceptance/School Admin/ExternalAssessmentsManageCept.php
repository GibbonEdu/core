<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'externalAssessments_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add External Assessment');

$addFormValues = array(
    'name'            => 'Test Assessment 1',
    'nameShort'       => 'TASS1',
    'description'     => 'For testing.',
    'active'          => 'Y',
    'allowFileUpload' => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonExternalAssessmentID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'externalAssessments_manage_edit.php', array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID));
$I->seeBreadcrumb('Edit External Assessment');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'            => 'Test Assessment 2',
    'nameShort'       => 'TASS2',
    'description'     => 'Also for testing.',
    'active'          => 'N',
    'allowFileUpload' => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Add Field --------------------------------------------
$I->amOnModulePage('School Admin', 'externalAssessments_manage_edit_field_add.php', array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID));
$I->seeBreadcrumb('Add Field');

$addFormValues = array(
    'name'     => 'Test Field 1',
    'category' => 'Test',
    'order'    => '1',
);
$I->selectFromDropdown('gibbonScaleID', 2);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonExternalAssessmentFieldID = $I->grabEditIDFromURL();

// Delete Field ------------------------------------------
$I->amOnModulePage('School Admin', 'externalAssessments_manage_edit_field_delete.php', array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID, 'gibbonExternalAssessmentFieldID' => $gibbonExternalAssessmentFieldID));
$I->seeBreadcrumb('Delete Grade');

$I->click('Yes');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'externalAssessments_manage_delete.php', array('gibbonExternalAssessmentID' => $gibbonExternalAssessmentID));
$I->seeBreadcrumb('Delete External Assessment');

$I->click('Yes');
$I->seeSuccessMessage();
