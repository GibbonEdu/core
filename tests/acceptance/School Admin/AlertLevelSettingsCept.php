<?php 
/**
 * @covers modules/School Admin/alertLevelSettings.php
 * @covers modules/School Admin/alertType_add.php
 * @covers modules/School Admin/alertType_edit.php
 * @covers modules/School Admin/alertType_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update Alert Level Settings and manage alert types');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'alertLevelSettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'name0'           => 'LowTest',
    'nameShort0'      => 'LT',
    'color0'          => '#000000',
    'colorBG0'        => '#111111',
    'description0'    => 'Low Test',

    'name1'           => 'MedTest',
    'nameShort1'      => 'MT',
    'color1'          => '#222222',
    'colorBG1'        => '#333333',
    'description1'    => 'Med Test',

    'name2'           => 'HighTest',
    'nameShort2'      => 'HT',
    'color2'          => '#444444',
    'colorBG2'        => '#555555',
    'description2'    => 'High Test',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

// Add Alert Type -----------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Type');

$addFormValues = array(
    'name'        => 'Test Alert Type',
    'tag'         => 'TA',
    'description' => 'Test alert type description',
    'adminOnly'   => 'N',
    'useLevels'   => 'N',
    'color'       => '#FF0000',
    'colorBG'     => '#FFCCCC',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonAlertTypeID = $I->grabEditIDFromURL();

// Edit Alert Type ----------------------------------------------
$I->amOnModulePage('School Admin', 'alertType_edit.php', array(
    'gibbonAlertTypeID' => $gibbonAlertTypeID
));
$I->seeBreadcrumb('Edit Type');

$I->seeInField('name', 'Test Alert Type');

$editFormValues = array(
    'tag'         => 'TB',
    'description' => 'Updated description',
    'active'      => 'Y',
    'adminOnly'   => 'Y',
    'color'       => '#00FF00',
    'colorBG'     => '#CCFFCC',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Alert Type --------------------------------------------
$I->amOnModulePage('School Admin', 'alertType_delete.php', array(
    'gibbonAlertTypeID' => $gibbonAlertTypeID
));

$I->click('Delete');
$I->seeSuccessMessage();

