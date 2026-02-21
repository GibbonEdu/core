<?php
/**
 * @covers modules/Calendar/calendar_event_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Event');
$I->loginAsAdmin();

// Get an existing event ID from the database
$gibbonCalendarEventID = $I->grabFromDatabase('gibbonCalendarEvent', 'gibbonCalendarEventID', []);

$I->amOnModulePage('Calendar', 'calendar_event_view.php', [
    'gibbonCalendarEventID' => $gibbonCalendarEventID
]);
$I->seeBreadcrumb('View Event');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
