<?php
/**
 * @covers modules/Calendar/calendar_event_editStaff_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage calendar event staff');
$I->loginAsAdmin();

// Create a calendar first if none exists
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'admin']);

$gibbonCalendarID = $I->haveInDatabase('gibbonCalendar', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Calendar for Staff',
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
    'name' => 'Test Event for Staff',
    'status' => 'Confirmed',
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d'),
    'allDay' => 'Y',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonCalendarEventID = $I->grabEditIDFromURL();

// Add staff to the event
$I->amOnModulePage('Calendar', 'calendar_event_edit.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);
$I->seeBreadcrumb('Edit Event');

$I->selectFromDropdown('staff', 1);
$I->selectFromDropdown('role', 1);

$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Get the staff person ID from the database
$gibbonCalendarEventPersonID = $I->grabFromDatabase('gibbonCalendarEventPerson', 'gibbonCalendarEventPersonID', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);

// Test Delete Staff Action ----------------------------

$I->amOnModulePage('Calendar', 'calendar_event_editStaff_delete.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID,
    'gibbonCalendarEventPersonID' => $gibbonCalendarEventPersonID
]);

$I->click('Delete');
$I->seeSuccessMessage();

// Clean up - delete the event
$I->amOnModulePage('Calendar', 'calendar_event_delete.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);

$I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->seeSuccessMessage();
