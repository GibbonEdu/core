<?php
/**
 * @covers modules/Data Updater/data_personal_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage personal data updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_personal_manage.php');
$I->seeBreadcrumb('Personal Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
