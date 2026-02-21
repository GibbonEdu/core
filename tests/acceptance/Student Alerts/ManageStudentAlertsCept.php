<?php
/**
 * @covers modules/Student Alerts/studentAlerts_manage.php
 * @covers modules/Student Alerts/studentAlerts_add.php
 * @covers modules/Student Alerts/studentAlerts_edit.php
 * @covers modules/Student Alerts/studentAlerts_delete.php
 * @covers modules/Student Alerts/studentAlerts_manage_status.php
 * @covers modules/Student Alerts/studentAlerts_manage_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit, manage status, view and delete a student alert');
$I->loginAsAdmin();
$I->amOnModulePage('Student Alerts', 'studentAlerts_manage.php');
$I->seeBreadcrumb('Manage Student Alerts');

// Search Test -----------------------------------------

$I->selectFromDropdown('gibbonPersonID', 1);
$I->submitForm('#search', []);
$I->dontSeeErrors();

// Add Global Alert ------------------------------------
$I->clickNavigation('Add Global Alert');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonPersonID', 1);
$I->selectFromDropdown('type', 1);
$I->selectFromDropdown('level', 1);
$I->selectFromDropdown('status', 1);

$formValues = array(
    'comment' => 'Test alert comment',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonAlertID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Student Alerts', 'studentAlerts_edit.php', array(
    'gibbonAlertID' => $gibbonAlertID,
));
$I->seeBreadcrumb('Edit');

$I->seeInFormFields('#content form', array(
    'comment' => 'Test alert comment',
));

$I->selectFromDropdown('level', 2);

$formValues = array(
    'comment' => 'Updated alert comment',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// View Alert ------------------------------------------
$I->amOnModulePage('Student Alerts', 'studentAlerts_manage_view.php', array(
    'gibbonAlertID' => $gibbonAlertID,
));
$I->seeBreadcrumb('View Alert');
$I->dontSeeErrors();

// Change Status (Decline) -----------------------------
$I->amOnModulePage('Student Alerts', 'studentAlerts_manage_status.php', array(
    'gibbonAlertID' => $gibbonAlertID,
    'status' => 'Declined',
));
$I->seeBreadcrumb('Alert Status');

$formValues = array(
    'notesStatus' => 'Declined for testing purposes',
);

$I->submitForm('#alertStatus', $formValues, 'Submit');
$I->seeSuccessMessage();

// Delete ----------------------------------------------
$I->amOnModulePage('Student Alerts', 'studentAlerts_delete.php', array(
    'gibbonAlertID' => $gibbonAlertID,
));

$I->click('Delete');
$I->seeSuccessMessage();
