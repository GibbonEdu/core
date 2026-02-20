<?php
/**
 * @covers modules/Reports/archive_manage.php
 * @covers modules/Reports/archive_manage_add.php
 * @covers modules/Reports/archive_manage_edit.php
 * @covers modules/Reports/archive_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage archives with full CRUD operations');

$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'archive_manage.php');
$I->seeBreadcrumb('Manage Archives');

// Add Default archive
$I->click('Add');
$I->seeBreadcrumb('Add Archive');
$I->dontSeeErrors();

$I->amOnModulePage('Reports', 'archive_manage.php');

// Edit Default archive
$I->click('Edit');
$I->seeBreadcrumb('Edit Archive');

$gibbonReportArchiveID = $I->grabValueFromURL('gibbonReportArchiveID');

$I->seeInField('name', 'Default Archive');
$I->fillField('name', 'Updated Archive');
$I->click('Submit');
$I->seeSuccessMessage();

// Restore original value
$I->fillField('name', 'Default Archive');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the archive
$I->amOnModulePage('Reports', 'archive_manage_delete.php', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
$I->dontSeeErrors();
