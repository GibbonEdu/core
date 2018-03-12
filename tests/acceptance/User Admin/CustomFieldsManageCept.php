<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete custom fields');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'userFields.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Custom Field');

$addFormValues = array(
    'name'                  => 'Test Field',
    'active'                => 'Y',
    'description'           => 'For Testing',
    'type'                  => 'varchar',
    'options'               => '123',
    'required'              => 'Y',
    'activeDataUpdater'     => '1',
    'activeApplicationForm' => '1',
);

$I->checkOption('input[type=checkbox][value=activePersonStudent]');
$I->checkOption('input[type=checkbox][value=activePersonOther]');

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonPersonFieldID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('User Admin', 'userFields_edit.php', array('gibbonPersonFieldID' => $gibbonPersonFieldID));
$I->seeBreadcrumb('Edit Custom Field');

$I->seeInFormFields('#content form', $addFormValues);
$I->seeCheckboxIsChecked('input[type=checkbox][value=activePersonStudent]');
$I->seeCheckboxIsChecked('input[type=checkbox][value=activePersonOther]');

$editFormValues = array(
    'name'                  => 'Test Field!',
    'active'                => 'N',
    'description'           => 'For Testing?',
    'type'                  => 'select',
    'options'               => 'One,Two,Three',
    'required'              => 'N',
    'activeDataUpdater'     => '0',
    'activeApplicationForm' => '0',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('User Admin', 'userFields_delete.php', array('gibbonPersonFieldID' => $gibbonPersonFieldID));
$I->seeBreadcrumb('Delete Custom Field');

$I->click('Yes');
$I->seeSuccessMessage();