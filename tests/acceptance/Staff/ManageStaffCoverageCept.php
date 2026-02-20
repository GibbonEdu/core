<?php
/**
 * @covers modules/Staff/coverage_manage.php
 * @covers modules/Staff/coverage_manage_add.php
 * @covers modules/Staff/coverage_manage_edit.php
 * @covers modules/Staff/coverage_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage staff coverage with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Staff', 'coverage_manage.php');
$I->seeBreadcrumb('Manage Staff Coverage');

// Add new coverage
$I->clickLink('Add');
$I->seeBreadcrumb('Add');
$I->selectFromDropdown('gibbonPersonIDStatus', 2);
$I->selectFromDropdown('gibbonPersonIDCoverage', 3);
$I->fillField('date', date('d/m/Y'));
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the coverage
$gibbonStaffCoverageID = $I->grabEditIDFromURL();
$I->amOnModulePage('Staff', 'coverage_manage_edit.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Edit');
$I->fillField('notesCoverage', 'Updated coverage notes');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the coverage
$I->amOnModulePage('Staff', 'coverage_manage_delete.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
