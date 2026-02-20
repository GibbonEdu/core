<?php
/**
 * @covers modules/Data Updater/data_medical_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage medical data updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_medical_manage.php');
$I->seeBreadcrumb('Medical Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
