<?php
/**
 * @covers modules/Timetable/report_viewAvailableSpace_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View available space details');
$I->loginAsAdmin();

// Get a timetable day
$gibbonTTDayID = $I->grabFromDatabase('gibbonTTDay', 'gibbonTTDayID', []);

$I->amOnModulePage('Timetable', 'report_viewAvailableSpace_view.php', ['gibbonTTDayID' => $gibbonTTDayID]);
