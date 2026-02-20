<?php
/**
 * @covers modules/Timetable/report_viewAvailableTeachers_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View available teachers details');
$I->loginAsAdmin();

// Get a timetable day
$gibbonTTDayID = $I->grabFromDatabase('gibbonTTDay', 'gibbonTTDayID', []);

$I->amOnModulePage('Timetable', 'report_viewAvailableTeachers_view.php', ['gibbonTTDayID' => $gibbonTTDayID]);
