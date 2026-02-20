<?php
/**
 * @covers modules/Messenger/groups_manage.php
 * @covers modules/Messenger/groups_manage_add.php
 * @covers modules/Messenger/groups_manage_edit.php
 * @covers modules/Messenger/groups_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage groups with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Messenger', 'groups_manage.php');
$I->seeBreadcrumb('Manage Groups');

// Add a new group
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Group');
$I->submitForm('#content form', ['members' => ['0000002746']]);

$I->seeSuccessMessage();

// Edit the group
$gibbonGroupID = $I->grabEditIDFromURL();
$I->amOnModulePage('Messenger', 'groups_manage_edit.php', ['gibbonGroupID' => $gibbonGroupID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Group');
$I->fillField('name', 'Updated Group');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the group
$I->amOnModulePage('Messenger', 'groups_manage_delete.php', ['gibbonGroupID' => $gibbonGroupID]);
$I->click('Delete');
$I->seeSuccessMessage();
