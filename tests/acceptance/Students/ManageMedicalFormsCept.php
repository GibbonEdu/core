<?php
/**
 * @covers modules/Students/medicalForm_manage.php
 * @covers modules/Students/medicalForm_manage_add.php
 * @covers modules/Students/medicalForm_manage_edit.php
 * @covers modules/Students/medicalForm_manage_delete.php
 * @covers modules/Students/medicalForm_manage_condition_add.php
 * @covers modules/Students/medicalForm_manage_condition_edit.php
 * @covers modules/Students/medicalForm_manage_condition_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage medical forms with nested conditions');
$I->loginAsAdmin();
$I->amOnModulePage('Students', 'medicalForm_manage.php');
$I->seeBreadcrumb('Manage Medical Forms');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#filter', []);
$I->dontSeeErrors();

$I->deleteFromDatabase('gibbonPersonMedical', ['gibbonPersonID' => '0000002746']);

// Add Medical Form ------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Medical Form');

$I->selectFromDropdown('gibbonPersonID', 1);

$formValues = array(
    'longTermMedication' => 'Y',
    'longTermMedicationDetails' => 'Test medication details',
    'comment' => 'Test medical comment',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalID = $I->grabEditIDFromURL();

// Edit Medical Form -----------------------------------

$I->amOnModulePage('Students', 'medicalForm_manage_edit.php', array('gibbonPersonMedicalID' => $gibbonPersonMedicalID));
$I->seeBreadcrumb('Edit Medical Form');

$I->seeInFormFields('#content form', array(
    'longTermMedication' => 'Y',
    'longTermMedicationDetails' => 'Test medication details',
    'comment' => 'Test medical comment',
));

$formValues = array(
    'longTermMedication' => 'N',
    'comment' => 'Updated medical comment',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Add Medical Condition -------------------------------

$I->amOnModulePage('Students', 'medicalForm_manage_edit.php', array('gibbonPersonMedicalID' => $gibbonPersonMedicalID));

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Condition');

$I->selectFromDropdown('name', 1);
$I->selectFromDropdown('gibbonAlertLevelID', 1);

$conditionValues = array(
    'triggers' => 'Test triggers',
    'reaction' => 'Test reaction',
    'response' => 'Test response',
    'medication' => 'Test medication',
    'comment' => 'Test condition comment',
);

$I->submitForm('#content form', $conditionValues, 'Submit');
$I->seeSuccessMessage();

$gibbonPersonMedicalConditionID = $I->grabEditIDFromURL();

// Edit Medical Condition ------------------------------

$I->amOnModulePage('Students', 'medicalForm_manage_condition_edit.php', array(
    'gibbonPersonMedicalID' => $gibbonPersonMedicalID,
    'gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID
));
$I->seeBreadcrumb('Edit Condition');

$I->seeInFormFields('#content form', array(
    'triggers' => 'Test triggers',
    'reaction' => 'Test reaction',
    'response' => 'Test response',
    'medication' => 'Test medication',
    'comment' => 'Test condition comment',
));

$conditionValues = array(
    'triggers' => 'Updated triggers',
    'reaction' => 'Updated reaction',
);

$I->submitForm('#content form', $conditionValues, 'Submit');
$I->seeSuccessMessage();

// Delete Medical Condition ----------------------------

$I->amOnModulePage('Students', 'medicalForm_manage_condition_delete.php', array(
    'gibbonPersonMedicalID' => $gibbonPersonMedicalID,
    'gibbonPersonMedicalConditionID' => $gibbonPersonMedicalConditionID
));

$I->click('Delete');
$I->seeSuccessMessage();

// Delete Medical Form ---------------------------------

$I->amOnModulePage('Students', 'medicalForm_manage_delete.php', array('gibbonPersonMedicalID' => $gibbonPersonMedicalID));

$I->click('Delete');
$I->seeSuccessMessage();
