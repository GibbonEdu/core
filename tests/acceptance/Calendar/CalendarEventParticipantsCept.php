<?php
/**
 * @covers modules/Calendar/calendar_event_participants.php
 * @covers modules/Calendar/calendar_event_participants_add.php
 * @covers modules/Calendar/calendar_event_participants_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage calendar event participants');
$I->loginAsAdmin();

// Create a calendar first if none exists
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'admin']);

$gibbonCalendarID = $I->haveInDatabase('gibbonCalendar', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Calendar for Participants',
    'color' => '#3A6CA8',
    'public' => 'Y',
    'sequenceNumber' => 1,
]);

// Add calendar editor permission
$I->haveInDatabase('gibbonCalendarEditor', [
    'gibbonCalendarID' => $gibbonCalendarID,
    'gibbonPersonID' => $gibbonPersonID,
]);

// Create a test event first
$I->amOnModulePage('Calendar', 'calendar_event_manage.php');
$I->seeBreadcrumb('Manage Events');

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Event');

$I->selectFromDropdown('gibbonCalendarID', 1);
$I->selectFromDropdown('gibbonCalendarEventTypeID', 1);

$formValues = [
    'name' => 'Test Event for Participants',
    'status' => 'Confirmed',
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d'),
    'allDay' => 'Y',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonCalendarEventID = $I->grabEditIDFromURL();

// Navigate to Participants Page -----------------------

$I->amOnModulePage('Calendar', 'calendar_event_participants.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);
$I->seeBreadcrumb('Edit Participants');
$I->dontSeeErrors();

// Test Add Participants Page --------------------------

$I->clickNavigation('Add Participants');
$I->seeBreadcrumb('Add Participants');
$I->dontSeeErrors();

// Create a participant directly in the database for delete test
$gibbonPersonIDParticipant = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', []);

$gibbonCalendarEventPersonID = $I->haveInDatabase('gibbonCalendarEventPerson', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID,
    'gibbonPersonID' => $gibbonPersonIDParticipant,
    'role' => 'Attendee',
    'gibbonPersonIDCreated' => $gibbonPersonID,
    'gibbonPersonIDModified' => $gibbonPersonID,
]);

// Test Delete Participant -----------------------------

$I->amOnModulePage('Calendar', 'calendar_event_participants_delete.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID,
    'gibbonCalendarEventPersonID' => $gibbonCalendarEventPersonID,
    'gibbonPersonID' => $gibbonPersonIDParticipant
]);

// $I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->seeSuccessMessage();

// Clean up - delete the event
$I->amOnModulePage('Calendar', 'calendar_event_delete.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);

$I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->seeSuccessMessage();
