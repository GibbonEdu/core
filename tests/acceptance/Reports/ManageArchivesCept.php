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

// Add a new archive
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Archive');
$I->fillField('path', 'reports');

$I->click('Submit');
$I->seeSuccessMessage();

// Edit the archive
$gibbonReportArchiveID = $I->grabEditIDFromURL();
$I->amOnModulePage('Reports', 'archive_manage_edit.php', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Archive');
$I->fillField('name', 'Updated Archive');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the archive
$I->amOnModulePage('Reports', 'archive_manage_delete.php', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
$I->click('Delete');
$I->seeSuccessMessage();
