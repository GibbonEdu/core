<?php
/**
 * @covers modules/Data Updater/data_updates.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check My Data Updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_updates.php');
$I->seeBreadcrumb('My Data Updates');
$I->see('This page shows all the data updates that are available to you.');
