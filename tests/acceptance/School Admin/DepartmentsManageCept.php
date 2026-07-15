<?php
/**
 * @covers modules/School Admin/department_manage.php
 * @covers modules/School Admin/department_manage_add.php
 * @covers modules/School Admin/department_manage_edit.php
 * @covers modules/School Admin/department_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'department_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Department');

$addFormValues = array(
    'type'           => 'Learning Area',
    'name'           => 'Test Department 1',
    'nameShort'      => 'TD1',
    'subjectListing' => 'Testing',
    'blurb'          => 'For testing.',
);

$I->attachFile('file', 'attachment.jpg');
$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonDepartmentID = $I->grabEditIDFromURL();
$file = $I->grabFromDatabase('gibbonDepartment', 'logo', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$I->assertNotEmpty($file);

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

$I->fillField('logo', '');
$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonDepartmentID = $I->grabValueFromURL('gibbonDepartmentID');
$I->seeInDatabase('gibbonDepartment', ['gibbonDepartmentID' => $gibbonDepartmentID, 'logo' => '']);

// Edit - File Upload ------------------------------------------------
$I->amOnModulePage('School Admin', 'department_manage_edit.php', array('gibbonDepartmentID' => $gibbonDepartmentID));

$I->attachFile('file', 'attachment2.png');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

$file2 = $I->grabFromDatabase('gibbonDepartment', 'logo', ['gibbonDepartmentID' => $gibbonDepartmentID]);
$I->assertNotEmpty($file2);

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'department_manage_delete.php', array('gibbonDepartmentID' => $gibbonDepartmentID));

$I->click('Delete');
$I->seeSuccessMessage();
