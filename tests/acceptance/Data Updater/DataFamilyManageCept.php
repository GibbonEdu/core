<?php
/**
 * @covers modules/Data Updater/data_family_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage family data updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_family_manage.php');
$I->seeBreadcrumb('Family Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
