<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete form groups');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'formGroup_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add');

$addFormValues = array(
    'name'       => 'Test 1',
    'nameShort'  => 'TR1',
    'attendance' => 'Y',
    'website'    => 'http://testing.test',
);

$I->selectFromDropdown('gibbonPersonIDTutor', 2);
$I->selectFromDropdown('gibbonPersonIDTutor2', 3);
$I->selectFromDropdown('gibbonPersonIDTutor3', 4);

$I->selectFromDropdown('gibbonPersonIDEA', -2);
$I->selectFromDropdown('gibbonPersonIDEA2', -3);
$I->selectFromDropdown('gibbonPersonIDEA3', -4);

$I->selectFromDropdown('gibbonSpaceID', 2);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');
$gibbonFormGroupID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'formGroup_manage_edit.php', array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));
$I->seeBreadcrumb('Edit');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'       => 'Test 2',
    'nameShort'  => 'TR2',
    'attendance' => 'N',
    'website'    => '',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'formGroup_manage_delete.php', array('gibbonFormGroupID' => $gibbonFormGroupID, 'gibbonSchoolYearID' => $gibbonSchoolYearID));

$I->click('Delete');
$I->seeSuccessMessage();
