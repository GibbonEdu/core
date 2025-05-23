<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete school year terms');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'schoolYearTerm_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Term');

$addFormValues = array(
    'sequenceNumber'     => '900',
    'name'               => 'Test Term 1',
    'nameShort'          => 'TT1',
    'firstDay'           => '2001-01-01',
    'lastDay'            => '2001-12-31',
);
$I->selectFromDropdown('gibbonSchoolYearID', -1);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonSchoolYearTermID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'schoolYearTerm_manage_edit.php', array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID));
$I->seeBreadcrumb('Edit Term');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'sequenceNumber'     => '999',
    'name'               => 'Test Term 2',
    'nameShort'          => 'TT2',
    'firstDay'           => '2020-01-01',
    'lastDay'            => '2020-12-31',
);
$I->selectFromDropdown('gibbonSchoolYearID', 2);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'schoolYearTerm_manage_delete.php', array('gibbonSchoolYearTermID' => $gibbonSchoolYearTermID));

$I->click('Delete');
$I->seeSuccessMessage();
