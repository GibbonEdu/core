<?php
/**
 * @covers modules/Data Updater/data_staff_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff data updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_staff_manage.php');
$I->seeBreadcrumb('Staff Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
