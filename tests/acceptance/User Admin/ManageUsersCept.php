<?php
/**
 * @covers modules/User Admin/user_manage.php
 * @covers modules/User Admin/user_manage_password.php
 * @covers modules/User Admin/user_manage_view_previousPhotos.php
 * @covers modules/User Admin/user_manage_view_status_log.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage users');
$I->loginAsAdmin();
$I->amOnModulePage('User Admin', 'user_manage.php');
$I->seeBreadcrumb('Manage Users');

// Get an existing user for testing
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

// Test Password Reset -----------------------------------
$I->amOnModulePage('User Admin', 'user_manage_password.php', [
    'gibbonPersonID' => $gibbonPersonID
]);
$I->seeBreadcrumb('Reset User Password');
$I->dontSeeErrors();

// Test View Previous Photos -----------------------------
$I->amOnModulePage('User Admin', 'user_manage_view_previousPhotos.php', [
    'gibbonPersonID' => $gibbonPersonID
]);
$I->dontSeeErrors();

// Test View Status Log ----------------------------------
$I->amOnModulePage('User Admin', 'user_manage_view_status_log.php', [
    'gibbonPersonID' => $gibbonPersonID
]);
$I->dontSeeErrors();
