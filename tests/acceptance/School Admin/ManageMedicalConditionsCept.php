<?php
/**
 * @covers modules/School Admin/medicalConditions_manage.php
 * @covers modules/School Admin/medicalConditions_manage_add.php
 * @covers modules/School Admin/medicalConditions_manage_edit.php
 * @covers modules/School Admin/medicalConditions_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage medical conditions');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'medicalConditions_manage.php');
$I->seeBreadcrumb('Manage Medical Conditions');

// Update Settings ------------------------------------------
$originalFormValues = $I->grabAllFormValues('#content form');
$I->seeInFormFields('#content form', $originalFormValues);

$newFormValues = array(
    'medicalConditionIntro' => '<p>Test medical condition intro</p>',
);

$I->submitForm('#content form', $newFormValues, 'Submit');
$I->seeSuccessMessage();

// Restore original settings
$I->amOnModulePage('School Admin', 'medicalConditions_manage.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->seeSuccessMessage();

// Add Medical Condition ------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Condition');

$addFormValues = array(
    'name'        => 'Test Condition',
    'description' => 'Test condition description',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonMedicalConditionID = $I->grabEditIDFromURL();

// Edit Medical Condition -----------------------------------
$I->amOnModulePage('School Admin', 'medicalConditions_manage_edit.php', array(
    'gibbonMedicalConditionID' => $gibbonMedicalConditionID
));
$I->seeBreadcrumb('Edit Condition');

$I->seeInField('name', 'Test Condition');

$editFormValues = array(
    'name'        => 'Updated Condition',
    'description' => 'Updated description',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Medical Condition ---------------------------------
$I->amOnModulePage('School Admin', 'medicalConditions_manage_delete.php', array(
    'gibbonMedicalConditionID' => $gibbonMedicalConditionID
));

$I->click('Delete');
$I->seeSuccessMessage();
