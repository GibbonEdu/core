<?php
/**
 * @covers modules/Individual Needs/investigations_manage.php
 * @covers modules/Individual Needs/investigations_manage_add.php
 * @covers modules/Individual Needs/investigations_manage_edit.php
 * @covers modules/Individual Needs/investigations_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage investigations with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Individual Needs', 'investigations_manage.php');
$I->seeBreadcrumb('Manage Investigations');

// Add a new investigation
$I->click('Add');
$I->seeBreadcrumb('Add');

$I->selectFromDropdown('gibbonPersonIDStudent', 1);
$I->fillField('date', date('Y-m-d'));
$I->fillField('reason', 'Test investigation reason');
$I->fillField('status', 'Referral');
$I->fillField('parentsInformed', 'N');
$I->fillField('parentsResponse', 'Test');

$I->submitForm('#content form', []);
$I->seeSuccessMessage();

// Edit the investigation
$gibbonINInvestigationID = $I->grabEditIDFromURL();
$I->amOnModulePage('Individual Needs', 'investigations_manage_edit.php', ['gibbonINInvestigationID' => $gibbonINInvestigationID]);
$I->seeBreadcrumb('Edit');
$I->fillField('reason', 'Updated investigation reason');

$I->submitForm('#content form', []);
$I->seeSuccessMessage();

// Delete the investigation
$I->amOnModulePage('Individual Needs', 'investigations_manage_delete.php', ['gibbonINInvestigationID' => $gibbonINInvestigationID]);
$I->click('Delete');
$I->seeSuccessMessage();
