<?php
/**
 * @covers modules/Timetable/report_viewAvailableSpaces.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View available facilities');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'report_viewAvailableSpaces.php');
$I->seeBreadcrumb('View Available Facilities');
