<?php
/**
 * @covers modules/Reports/reports_manage.php
 * @covers modules/Reports/reports_manage_add.php
 * @covers modules/Reports/reports_manage_edit.php
 * @covers modules/Reports/reports_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage reports with full CRUD operations');
$I->loginAsAdmin();
$I->amOnModulePage('Reports', 'reports_manage.php');
$I->seeBreadcrumb('Manage Reports');

// Add a new report
$I->clickLink('Add');
$I->seeBreadcrumb('Add');
$I->fillField('name', 'Test Report');
$I->selectFromDropdown('gibbonSchoolYearID', 2);
$I->click('Submit');
$I->seeSuccessMessage();

// Edit the report
$gibbonReportID = $I->grabEditIDFromURL();
$I->amOnModulePage('Reports', 'reports_manage_edit.php', ['gibbonReportID' => $gibbonReportID]);
$I->seeBreadcrumb('Edit');
$I->seeInField('name', 'Test Report');
$I->fillField('name', 'Updated Report');
$I->click('Submit');
$I->seeSuccessMessage();

// Delete the report
$I->amOnModulePage('Reports', 'reports_manage_delete.php', ['gibbonReportID' => $gibbonReportID]);
$I->seeBreadcrumb('Delete');
$I->click('Yes');
$I->seeSuccessMessage();
