<?php
/**
 * @covers modules/Timetable/tt_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View timetable by person');
$I->loginAsAdmin();

$I->amOnModulePage('Timetable', 'tt.php');
$I->seeBreadcrumb('View Timetable by Person');

// Get a person with a timetable
$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Timetable', 'tt_view.php', [
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonSchoolYearID' => $gibbonSchoolYearID
]);
$I->seeBreadcrumb('View Timetable');
