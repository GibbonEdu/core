<?php
/**
 * @covers modules/Staff/staff_view.php
 * @covers modules/Staff/staff_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View staff profile');
$I->loginAsAdmin();

$I->amOnModulePage('Staff', 'staff_view.php');
$I->seeBreadcrumb('Staff Directory');

// Get a staff member
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingadmin']);

$I->amOnModulePage('Staff', 'staff_view_details.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeBreadcrumb('Staff Directory');
