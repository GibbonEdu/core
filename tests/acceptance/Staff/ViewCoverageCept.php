<?php
/**
 * @covers modules/Staff/coverage_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View coverage');
$I->loginAsAdmin();

// Get an existing staff coverage
$gibbonStaffCoverageID = $I->grabFromDatabase('gibbonStaffCoverage', 'gibbonStaffCoverageID', []);

$I->amOnModulePage('Staff', 'coverage_view.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Open Requests');
