<?php
/**
 * @covers modules/Staff/coverage_my.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View my coverage');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Staff', 'coverage_my.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('My Coverage');
