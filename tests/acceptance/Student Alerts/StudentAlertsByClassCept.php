<?php
/**
 * @covers modules/Student Alerts/report_alertsByClass.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student alerts by class');
$I->loginAsAdmin();
$I->amOnModulePage('Student Alerts', 'report_alertsByClass.php');
$I->seeBreadcrumb('Student Alerts by Class');
