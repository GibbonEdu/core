<?php
/**
 * @covers modules/Timetable/tt.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View timetable by person');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'tt.php');
$I->seeBreadcrumb('View Timetable by Person');
