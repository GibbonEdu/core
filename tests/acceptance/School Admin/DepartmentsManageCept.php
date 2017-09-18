<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'department_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Learning Area');

$addFormValues = array(
    'type'           => 'Learning Area',
    'name'           => 'Test Department 1',
    'nameShort'      => 'TD1',
    'subjectListing' => 'Testing',
    'blurb'          => 'For testing.',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonDepartmentID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'department_manage_edit.php', array('gibbonDepartmentID' => $gibbonDepartmentID));
$I->seeBreadcrumb('Edit Department');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'type'           => 'Administration',
    'name'           => 'Test Department 2',
    'nameShort'      => 'TD2',
    'subjectListing' => 'More Testing',
    'blurb'          => 'Also for testing.',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'department_manage_delete.php', array('gibbonDepartmentID' => $gibbonDepartmentID));
$I->seeBreadcrumb('Delete Department');

$I->click('Yes');
$I->seeSuccessMessage();
