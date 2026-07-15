<?php
/**
 * @covers modules/School Admin/activitySettings.php
 * @covers modules/School Admin/activitySettings_type_add.php
 * @covers modules/School Admin/activitySettings_type_edit.php
 * @covers modules/School Admin/activitySettings_type_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('update Activity Settings and manage activity types');
$I->loginAsAdmin();
$I->amOnModulePage('School Admin', 'activitySettings.php');

// Grab Original Settings --------------------------------------

$originalFormValues = $I->grabAllFormValues();
$I->seeInFormFields('#content form', $originalFormValues);

// Make Changes ------------------------------------------------

$newFormValues = array(
    'dateType'                      => 'Term',
    'maxPerTerm'                    => '3',
    'access'                        => 'View',
    'payment'                       => 'Single + Per Activity',
    'disableExternalProviderSignup' => 'Y',
    'hideExternalProviderCost'      => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Verify Results ----------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $newFormValues);

// Restore Original Settings -----------------------------------

$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);

// Add Activity Type --------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add');

$addFormValues = array(
    'name'           => 'Test Activity Type',
    'description'    => 'Test Description',
    'access'         => 'Register',
    'enrolmentType'  => 'Competitive',
    'maxPerStudent'  => '2',
    'waitingList'    => 'Y',
    'backupChoice'   => 'Y',
);

$I->submitForm('#content form', $addFormValues, 'Submit');
$I->seeSuccessMessage();

$gibbonActivityTypeID = $I->grabEditIDFromURL();

// Edit Activity Type -------------------------------------------
$I->amOnModulePage('School Admin', 'activitySettings_type_edit.php', array(
    'gibbonActivityTypeID' => $gibbonActivityTypeID
));
$I->seeBreadcrumb('Edit');

$I->seeInField('name', 'Test Activity Type');

$editFormValues = array(
    'description'    => 'Updated Description',
    'access'         => 'View',
    'enrolmentType'  => 'Selection',
    'maxPerStudent'  => '3',
    'waitingList'    => 'N',
    'backupChoice'   => 'N',
);

$I->submitForm('#content form', $editFormValues, 'Submit');
$I->seeSuccessMessage();

// Delete Activity Type -----------------------------------------
$I->amOnModulePage('School Admin', 'activitySettings_type_delete.php', array(
    'gibbonActivityTypeID' => $gibbonActivityTypeID
));

$I->click('Delete');
$I->seeSuccessMessage();

