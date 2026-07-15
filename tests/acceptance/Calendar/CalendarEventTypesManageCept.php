<?php
/**
 * @covers modules/Calendar/calendar_eventTypes_manage.php
 * @covers modules/Calendar/calendar_eventTypes_manage_addEdit.php
 * @covers modules/Calendar/calendar_eventTypes_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a calendar event type');
$I->loginAsAdmin();
$I->amOnModulePage('Calendar', 'calendar_eventTypes_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Event Type');

$formValues = array(
    'type' => 'Test Event Type',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonCalendarEventTypeID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Calendar', 'calendar_eventTypes_manage_addEdit.php', array(
    'gibbonCalendarEventTypeID' => $gibbonCalendarEventTypeID
));
$I->seeBreadcrumb('Edit Event Type');

$I->seeInField('type', 'Test Event Type');

$formValues = array(
    'type' => 'Updated Test Event Type',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Calendar', 'calendar_eventTypes_manage_delete.php', array(
    'gibbonCalendarEventTypeID' => $gibbonCalendarEventTypeID
));

$I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');
