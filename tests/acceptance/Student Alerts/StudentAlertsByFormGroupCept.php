<?php
/**
 * @covers modules/Student Alerts/report_alertsByFormGroup.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View student alerts by form group');
$I->loginAsAdmin();
$I->amOnModulePage('Student Alerts', 'report_alertsByFormGroup.php');
$I->seeBreadcrumb('Student Alerts by Form Group');
