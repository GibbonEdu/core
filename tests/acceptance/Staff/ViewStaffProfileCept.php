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
$I->seeElement('a[href*="courseEnrolment_manage_byPerson_edit.php"][href*="type=Staff"]');
$I->seeElement('a[href*="tt_manage_subscription.php"]');

$I->amOnModulePage('Staff', 'staff_view_details.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'subpage' => 'Timetable',
]);
$I->seeElement('a[href*="courseEnrolment_manage_byPerson_edit.php"][href*="type=Staff"][href*="allUsers=on"]');
$I->dontSeeElement('a[href*="tt_manage_subscription.php"]');

$otherPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'testingteacher']);
$I->amOnModulePage('Staff', 'staff_view_details.php', ['gibbonPersonID' => $otherPersonID]);
$I->seeElement('a[href*="courseEnrolment_manage_byPerson_edit.php"][href*="type=Staff"]');
$I->dontSeeElement('a[href*="tt_manage_subscription.php"]');
