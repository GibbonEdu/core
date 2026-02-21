<?php
/**
 * @covers modules/Timetable/spaceChange_manage.php
 * @covers modules/Timetable/spaceChange_manage_add.php
 * @covers modules/Timetable/spaceChange_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add and delete a facility change');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'spaceChange_manage.php');
$I->seeBreadcrumb('Manage Facility Changes');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Add ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Facility Change');
$I->dontSeeErrors();

// Create test data directly in database
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);
$gibbonSpaceID = $I->grabFromDatabase('gibbonSpace', 'gibbonSpaceID', []);

// Get a future timetable slot
$gibbonTTDayRowClassID = $I->grabFromDatabase('gibbonTTDayRowClass', 'gibbonTTDayRowClassID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

$gibbonTTSpaceChangeID = $I->haveInDatabase('gibbonTTSpaceChange', [
    'gibbonTTDayRowClassID' => $gibbonTTDayRowClassID,
    'gibbonSpaceID' => $gibbonSpaceID,
    'gibbonPersonID' => $gibbonPersonID,
    'date' => date('Y-m-d', strtotime('+1 day')),
]);

// Delete ------------------------------------------------
$I->amOnModulePage('Timetable', 'spaceChange_manage_delete.php', array(
    'gibbonTTSpaceChangeID' => $gibbonTTSpaceChangeID,
    'gibbonCourseClassID' => $gibbonCourseClassID
));

$I->click('Delete');
$I->seeSuccessMessage();
