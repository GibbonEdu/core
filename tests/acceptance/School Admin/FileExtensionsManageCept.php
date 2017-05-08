<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'fileExtensions_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add File Extension');

$addFormValues = array(
    'extension' => 'foo',
    'name'      => 'Test Extension 1',
    'type'      => 'Document',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonFileExtensionID = $I->grabEditIDFromURL();

// Invalid ------------------------------------------------

$invalidFormValues = array(
    'extension' => 'php',
    'name'      => 'Test PHP',
    'type'      => 'Other',
);

$I->submitForm('#content form', $invalidFormValues, 'Submit');
$I->seeErrorMessage();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'fileExtensions_manage_edit.php', array('gibbonFileExtensionID' => $gibbonFileExtensionID));
$I->seeBreadcrumb('Edit File Extension');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'extension' => 'bar',
    'name'      => 'Test Extension 2',
    'type'      => 'Other',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'fileExtensions_manage_delete.php', array('gibbonFileExtensionID' => $gibbonFileExtensionID));
$I->seeBreadcrumb('Delete File Extension');

$I->click('Yes');
$I->seeSuccessMessage();
