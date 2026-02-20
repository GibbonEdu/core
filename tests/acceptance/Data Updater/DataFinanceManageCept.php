<?php
/**
 * @covers modules/Data Updater/data_finance_manage.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage finance data updates');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_finance_manage.php');
$I->seeBreadcrumb('Finance Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
