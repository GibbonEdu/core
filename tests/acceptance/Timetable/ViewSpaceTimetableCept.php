<?php
/**
 * @covers modules/Timetable/tt_space_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View space timetable');
$I->loginAsAdmin();

// Get a space
$gibbonSpaceID = $I->grabFromDatabase('gibbonSpace', 'gibbonSpaceID', []);
$gibbonTTID = $I->grabFromDatabase('gibbonTT', 'gibbonTTID', []);

$I->amOnModulePage('Timetable', 'tt_space_view.php', [
    'gibbonSpaceID' => $gibbonSpaceID,
    'gibbonTTID' => $gibbonTTID
]);
$I->seeBreadcrumb('View Timetable by Facility');
