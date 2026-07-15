<?php
/**
 * @covers modules/Staff/coverage_manage.php
 * @covers modules/Staff/coverage_manage_add.php
 * @covers modules/Staff/coverage_manage_edit.php
 * @covers modules/Staff/coverage_manage_edit_edit.php
 * @covers modules/Staff/coverage_manage_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Manage staff coverage with full CRUD operations');
$I->loginAsAdmin();

// Change Staff Settings -----------------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$originalFormValues = $I->grabAllFormValues();

$newFormValues = array(
    'coverageInternal'          => 'Y',
);

$I->submitForm('#content form', $newFormValues, 'Submit');

// Manage Coverage -----------------------------------

$I->amOnModulePage('Staff', 'coverage_manage.php');
$I->seeBreadcrumb('Manage Staff Coverage');

// Add new coverage
$I->click('Add', 'a');
$I->seeBreadcrumb('Add');
$I->fillField('dateStart', '2026-02-20');
$I->fillField('dateEnd', '2026-02-20');
$I->selectFromDropdown('gibbonPersonIDCoverage', 1);
$I->selectFromDropdown('gibbonPersonID', 1);
$I->selectFromDropdown('status', 1);
$I->fillField('reason', 'Test coverage');

$I->submitForm('#content form', ['requestDates' => ['2026-02-20']]);
$I->seeSuccessMessage();

// Edit the coverage
$gibbonStaffCoverageID = $I->grabEditIDFromURL();
$I->amOnModulePage('Staff', 'coverage_manage_edit.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Edit');
$I->fillField('notesStatus', 'Updated coverage notes');
$I->click('Submit');
$I->seeSuccessMessage();

// Get coverage date ID for nested edit ----------------

$gibbonStaffCoverageDateID = $I->grabFromDatabase('gibbonStaffCoverageDate', 'gibbonStaffCoverageDateID', [
    'gibbonStaffCoverageID' => $gibbonStaffCoverageID
]);

// Edit Nested Coverage Date ---------------------------

$I->amOnModulePage('Staff', 'coverage_manage_edit_edit.php', [
    'gibbonStaffCoverageID' => $gibbonStaffCoverageID,
    'gibbonStaffCoverageDateID' => $gibbonStaffCoverageDateID
]);
$I->seeBreadcrumb('Edit');

$I->fillField('reason', 'Updated coverage date notes');
$I->submitForm('#content form', [], 'Submit');
$I->seeSuccessMessage();

// Delete the coverage
$I->amOnModulePage('Staff', 'coverage_manage_delete.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->click('Delete');
$I->seeSuccessMessage();

// Restore Original Settings -----------------------------------
$I->amOnModulePage('User Admin', 'staffSettings.php');
$I->submitForm('#content form', $originalFormValues, 'Submit');
$I->see('Your request was completed successfully.', '.success');
$I->seeInFormFields('#content form', $originalFormValues);
