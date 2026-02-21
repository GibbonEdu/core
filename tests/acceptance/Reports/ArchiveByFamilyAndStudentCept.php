<?php
/**
 * @covers modules/Reports/archive_byFamily.php
 * @covers modules/Reports/archive_byStudent.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view reports archive by family and by student');
$I->loginAsAdmin();

// Test Archive by Family ----------------------------------

$I->amOnModulePage('Reports', 'archive_byFamily.php');
$I->seeBreadcrumb('View Reports');

// Basic Check
$I->dontSeeErrors();

// Test Archive by Student ---------------------------------

$I->amOnModulePage('Reports', 'archive_byStudent.php');
$I->seeBreadcrumb('View by Student');

// Basic Check
$I->dontSeeErrors();

// Filter Test ---------------------------------------------

$I->fillField('search', 'test');
$I->selectFromDropdown('gibbonYearGroupID', 1);
$I->submitForm('#content form', []);
$I->dontSeeErrors();

// Clear Filters Test --------------------------------------

$I->click('Clear Filters');
$I->dontSeeErrors();
