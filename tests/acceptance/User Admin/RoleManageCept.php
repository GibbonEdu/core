<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a role');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'role_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Role');

$formValues = array(
    'category'         => 'Other',
    'name'             => 'Testing Role',
    'nameShort'        => 'TSTR',
    'description'      => 'For testing.',
    'type'             => 'Additional',
    'pastYearsLogin'   => 'N',
    'futureYearsLogin' => 'N',
    'restriction'      => 'Same Role',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonRoleID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('User Admin', 'role_manage_edit.php', array('gibbonRoleID' => $gibbonRoleID));
$I->seeBreadcrumb('Edit Role');

$I->seeInFormFields('#content form', $formValues);

$formValues = array(
    'category'         => 'Staff',
    'name'             => 'Testing Role Too',
    'nameShort'        => 'TST2',
    'description'      => 'Also for testing.',
    'type'             => 'Additional',
    'pastYearsLogin'   => 'Y',
    'futureYearsLogin' => 'Y',
    'restriction'      => 'None',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('User Admin', 'role_manage_delete.php', array('gibbonRoleID' => $gibbonRoleID));
$I->seeBreadcrumb('Delete Role');

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

