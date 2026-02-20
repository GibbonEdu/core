<?php
/**
 * @covers modules/Timetable/tt_manage_subscription.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage timetable subscription');
$I->loginAsAdmin();
$I->amOnModulePage('Timetable', 'tt_manage_subscription.php');
$I->seeBreadcrumb('Manage Timetable Subscription');
