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
$I->clickLink('Add');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Investigation');
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the investigation
$gibbonINInvestigationID = $I->grabEditIDFromURL();
$I->amOnModulePage('Individual Needs', 'investigations_manage_edit.php', ['gibbonINInvestigationID' => $gibbonINInvestigationID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Investigation');
$I->fillField('name', 'Updated Investigation');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the investigation
$I->amOnModulePage('Individual Needs', 'investigations_manage_delete.php', ['gibbonINInvestigationID' => $gibbonINInvestigationID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
