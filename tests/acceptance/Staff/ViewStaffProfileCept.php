<?php
/**
 * @covers modules/Staff/staff_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View staff profile');
$I->loginAsAdmin();

// Get a staff member
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->amOnModulePage('Staff', 'staff_view.php', ['gibbonPersonID' => $gibbonPersonID]);
$I->seeBreadcrumb('View Staff');
