<?php
/**
 * @covers modules/Data Updater/data_staff.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Update Staff Data');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_staff.php');
$I->seeBreadcrumb('Update Staff Data');
$I->see('This page allows a user to request selected data updates for any staff record.');
