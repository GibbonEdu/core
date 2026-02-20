<?php
/**
 * @covers modules/Timetable/spaceBooking_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage facility bookings');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'spaceBooking_manage.php');
$I->seeBreadcrumb('Manage Facility Bookings');
