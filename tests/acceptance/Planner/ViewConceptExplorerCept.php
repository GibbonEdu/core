<?php
/**
 * @covers modules/Planner/conceptExplorer.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View concept explorer');
$I->loginAsAdmin();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Planner', 'conceptExplorer.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('Concept Explorer');
