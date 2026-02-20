<?php
/**
 * @covers modules/Planner/units_public.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View public units');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Planner', 'units_public.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Unit Planner');
