<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete string replacements');
$I->loginAsAdmin();
$I->amOnModulePage('System Admin', 'stringReplacement_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add String');

$addFormValues = array(
    'original'      => 'First String',
    'replacement'   => 'Second String',
    'mode'          => 'Whole',
    'caseSensitive' => 'Y',
    'priority'      => '0',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonStringID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('System Admin', 'stringReplacement_manage_edit.php', array('gibbonStringID' => $gibbonStringID));
$I->seeBreadcrumb('Edit String');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'original'      => 'Third String',
    'replacement'   => 'Fourth String',
    'mode'          => 'Partial',
    'caseSensitive' => 'N',
    'priority'      => '10',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('System Admin', 'stringReplacement_manage_delete.php', array('gibbonStringID' => $gibbonStringID));
$I->seeBreadcrumb('Delete String');

$I->click('Yes');
$I->seeSuccessMessage();
