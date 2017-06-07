<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete something');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'space_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Facility');

$addFormValues = array(
    'name'            => 'Test Facility 1',
    'type'            => 'Other',
    'capacity'        => '24',
    'computer'        => 'Y',
    'computerStudent' => '10',
    'projector'       => 'Y',
    'tv'              => 'Y',
    'dvd'             => 'Y',
    'hifi'            => 'Y',
    'speakers'        => 'Y',
    'iwb'             => 'Y',
    'phoneInternal'   => '2400',
    'phoneExternal'   => '',
    'comment'         => 'For testing.',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonSpaceID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('School Admin', 'space_manage_edit.php', array('gibbonSpaceID' => $gibbonSpaceID));
$I->seeBreadcrumb('Edit Facility');

$I->seeInFormFields('#content form', $addFormValues);

$editFormValues = array(
    'name'            => 'Test Facility 2',
    'type'            => 'Classroom',
    'capacity'        => '42',
    'computer'        => 'N',
    'computerStudent' => '0',
    'projector'       => 'N',
    'tv'              => 'N',
    'dvd'             => 'N',
    'hifi'            => 'N',
    'speakers'        => 'N',
    'iwb'             => 'N',
    'phoneInternal'   => '',
    'phoneExternal'   => '4200',
    'comment'         => 'Also for testing.',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete ------------------------------------------------
$I->amOnModulePage('School Admin', 'space_manage_delete.php', array('gibbonSpaceID' => $gibbonSpaceID));
$I->seeBreadcrumb('Delete Facility');

$I->click('Yes');
$I->seeSuccessMessage();
