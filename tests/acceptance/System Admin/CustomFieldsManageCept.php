<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete custom fields');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'customFields.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Custom Field');

$addFormValues = array(
    'context'               => 'User',
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

$gibbonCustomFieldID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('System Admin', 'customFields_edit.php', array('gibbonCustomFieldID' => $gibbonCustomFieldID));
$I->seeBreadcrumb('Edit Custom Field');

$I->seeInFormFields('#content form', $addFormValues);
$I->seeCheckboxIsChecked('input[type=checkbox][value=activePersonStudent]');
$I->seeCheckboxIsChecked('input[type=checkbox][value=activePersonOther]');

$editFormValues = array(
    'context'               => 'User',
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
$I->amOnModulePage('System Admin', 'customFields_delete.php', array('gibbonCustomFieldID' => $gibbonCustomFieldID));

$I->click('Delete');
$I->seeSuccessMessage();
