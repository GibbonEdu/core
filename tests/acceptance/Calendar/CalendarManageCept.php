<?php
/**
 * @covers modules/Calendar/calendar_manage.php
 * @covers modules/Calendar/calendar_manage_addEdit.php
 * @covers modules/Calendar/calendar_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add, edit and delete a calendar');
$I->loginAsAdmin();
$I->amOnModulePage('Calendar', 'calendar_manage.php');

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Calendar');

$gibbonSchoolYearID = $I->grabValueFromURL('gibbonSchoolYearID');

$formValues = array(
    'name'        => 'Test Calendar',
    'description' => 'Test calendar description',
    'color'       => '#FF0000',
    'public'      => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

$gibbonCalendarID = $I->grabEditIDFromURL();

// Edit ------------------------------------------------
$I->amOnModulePage('Calendar', 'calendar_manage_addEdit.php', array(
    'gibbonCalendarID' => $gibbonCalendarID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));
$I->seeBreadcrumb('Edit Calendar');

$I->seeInField('name', 'Test Calendar');

$formValues = array(
    'name'        => 'Updated Test Calendar',
    'description' => 'Updated description',
    'public'      => 'Y',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');

// Delete ------------------------------------------------
$I->amOnModulePage('Calendar', 'calendar_manage_delete.php', array(
    'gibbonCalendarID' => $gibbonCalendarID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
));

$I->fillField('confirm', 'Delete');
$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');
