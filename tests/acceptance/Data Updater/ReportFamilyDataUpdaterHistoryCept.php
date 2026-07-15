<?php
/**
 * @covers modules/Data Updater/report_family_dataUpdaterHistory.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Family Data Updater History report');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'report_family_dataUpdaterHistory.php');
$I->seeBreadcrumb('Family Data Updater History');
$I->see('This report allows a user to select a range of families');
