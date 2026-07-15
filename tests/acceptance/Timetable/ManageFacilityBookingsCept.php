<?php
/**
 * @covers modules/Timetable/spaceBooking_manage.php
 * @covers modules/Timetable/spaceBooking_manage_add.php
 * @covers modules/Timetable/spaceBooking_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add and delete a facility booking');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'spaceBooking_manage.php');
$I->seeBreadcrumb('Manage Facility Bookings');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Facility Booking');
$I->dontSeeErrors();

// Create a test booking directly in the database
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonSpaceID = $I->grabFromDatabase('gibbonSpace', 'gibbonSpaceID', ['bookable' => 'Y']);

$gibbonTTSpaceBookingID = $I->haveInDatabase('gibbonTTSpaceBooking', [
    'foreignKey' => 'gibbonSpaceID',
    'foreignKeyID' => $gibbonSpaceID,
    'gibbonPersonID' => $gibbonPersonID,
    'date' => date('Y-m-d', strtotime('+1 day')),
    'timeStart' => '09:00:00',
    'timeEnd' => '10:00:00',
    'reason' => 'Test Booking',
]);

// Delete ------------------------------------------------
$I->amOnModulePage('Timetable', 'spaceBooking_manage_delete.php', array(
    'gibbonTTSpaceBookingID' => $gibbonTTSpaceBookingID
));

$I->click('Delete');
$I->seeSuccessMessage();
