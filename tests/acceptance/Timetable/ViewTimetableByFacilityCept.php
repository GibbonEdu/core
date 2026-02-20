<?php
/**
 * @covers modules/Timetable/tt_space.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View timetable by facility');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'tt_space.php');
$I->seeBreadcrumb('View Timetable by Facility');
