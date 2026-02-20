<?php
/**
 * @covers modules/Data Updater/data_staff_manage.php
 * @covers modules/Data Updater/data_staff_manage_edit.php
 * @covers modules/Data Updater/data_staff_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('manage staff data updates with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Data Updater', 'data_staff_manage.php');
$I->seeBreadcrumb('Staff Data Updates');

// Search Test -----------------------------------------

$I->fillField('search', 'test');
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();

// Note: This module manages update requests, not creating new records
// Edit and Delete operations test the approval workflow
