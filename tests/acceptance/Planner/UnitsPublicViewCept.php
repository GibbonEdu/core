<?php
/**
 * @covers modules/Planner/units_public_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Unit (Public)');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

// Get a public unit
$gibbonUnitID = $I->grabFromDatabase('gibbonUnit', 'gibbonUnitID', ['sharedPublic' => 'Y']);

$I->amOnModulePage('Planner', 'units_public_view.php', [
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
    'gibbonUnitID' => $gibbonUnitID,
    'sidebar' => 'false'
]);
$I->seeBreadcrumb('View Unit');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
