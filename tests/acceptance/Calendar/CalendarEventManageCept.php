<?php
/**
 * @covers modules/Calendar/calendar_event_manage.php
 * @covers modules/Calendar/calendar_event_add.php
 * @covers modules/Calendar/calendar_event_edit.php
 * @covers modules/Calendar/calendar_event_delete.php
 * @covers modules/Calendar/calendar_event_notify.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit, notify and delete a calendar event');
$I->loginAsAdmin();

$I->amOnModulePage('Calendar', 'calendar_event_manage.php');
$I->seeBreadcrumb('Manage Events');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#filters', []);
$I->dontSeeErrors();

// Create a calendar first if none exists
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['username' => 'admin']);

$gibbonCalendarID = $I->haveInDatabase('gibbonCalendar', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'name' => 'Test Calendar',
    'color' => '#3A6CA8',
    'public' => 'Y',
    'sequenceNumber' => 1,
]);

// Add calendar editor permission
$I->haveInDatabase('gibbonCalendarEditor', [
    'gibbonCalendarID' => $gibbonCalendarID,
    'gibbonPersonID' => $gibbonPersonID,
]);

// Reload page to see Add button
$I->amOnModulePage('Calendar', 'calendar_event_manage.php');

// Add ------------------------------------------------

$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Event');

$I->selectFromDropdown('gibbonCalendarID', 1);
$I->selectFromDropdown('gibbonCalendarEventTypeID', 1);

$formValues = [
    'name' => 'Test Calendar Event',
    'status' => 'Confirmed',
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+1 day')),
    'allDay' => 'Y',
    'locationType' => 'Internal',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

$gibbonCalendarEventID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------

$I->amOnModulePage('Calendar', 'calendar_event_edit.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);
$I->seeBreadcrumb('Edit Event');

$I->seeInFormFields('#content form', [
    'name' => 'Test Calendar Event',
]);

$formValues = [
    'name' => 'Updated Calendar Event',
    'status' => 'Tentative',
    'dateStart' => date('Y-m-d'),
    'dateEnd' => date('Y-m-d', strtotime('+2 days')),
    'allDay' => 'Y',
];

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Notify Staff ----------------------------------------

$I->amOnModulePage('Calendar', 'calendar_event_notify.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);
$I->seeBreadcrumb('Notify Staff');

// This page requires attendees, so we just check it loads
$I->dontSeeErrors();

// Delete ----------------------------------------------

$I->amOnModulePage('Calendar', 'calendar_event_delete.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);

$I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->seeSuccessMessage();
