<?php
/**
 * @covers modules/Staff/coverage_availability.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View coverage availability');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Staff', 'coverage_availability.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Edit Availability');
