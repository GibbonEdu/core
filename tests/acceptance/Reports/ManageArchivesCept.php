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

$I->fillField('name', 'Test Archive');
$I->fillField('path', '/temp');

$I->click('Submit');
$I->seeSuccessMessage();

$gibbonReportArchiveID = $I->grabEditIDFromURL();

// Edit Default archive
$I->amOnModulePage('Reports', 'archive_manage_edit.php', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
$I->seeBreadcrumb('Edit Archive');

$I->seeInField('name', 'Test Archive');
$I->fillField('name', 'Updated Archive');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the archive
$I->amOnModulePage('Reports', 'archive_manage_delete.php', ['gibbonReportArchiveID' => $gibbonReportArchiveID]);
$I->dontSeeErrors();

$I->fillField('confirm', 'Delete');
$I->click('Delete');
$I->see('Your request was completed successfully.', '.success');
