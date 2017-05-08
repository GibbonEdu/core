<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete houses');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'house_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add House');

$addFormValues = array(
    'name'      => 'Test House',
    'nameShort' => 'TH1',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonHouseID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_edit.php', array('gibbonHouseID' => $gibbonHouseID));
$I->seeBreadcrumb('Edit House');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'      => 'House Test',
    'nameShort' => 'TH2',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'house_manage_delete.php', array('gibbonHouseID' => $gibbonHouseID));
$I->seeBreadcrumb('Delete House');

$I->click('Yes');
$I->seeSuccessMessage();
