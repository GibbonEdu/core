<?php
/**
 * @covers modules/Staff/coverage_view_details.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View coverage details');
$I->loginAsAdmin();

// Get an existing staff coverage ID
$gibbonStaffCoverageID = $I->grabFromDatabase('gibbonStaffCoverage', 'gibbonStaffCoverageID', []);

$I->amOnModulePage('Staff', 'coverage_view_details.php', ['gibbonStaffCoverageID' => $gibbonStaffCoverageID]);
$I->seeBreadcrumb('Coverage');
