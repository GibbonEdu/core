<?php
/**
 * @covers modules/Timetable/tt_manage_subscription.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage timetable subscription');
$I->loginAsAdmin();

$gibbonPersonID = $I->grabFromDatabase('gibbonPerson', 'gibbonPersonID', ['status' => 'Full']);

$I->amOnModulePage('Timetable', 'tt_manage_subscription.php', ['gibbonPersonID' => $gibbonPersonID]);
