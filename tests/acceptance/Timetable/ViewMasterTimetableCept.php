<?php
/**
 * @covers modules/Timetable/tt_master.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View master timetable');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'tt_master.php');
$I->seeBreadcrumb('View Master Timetable');
