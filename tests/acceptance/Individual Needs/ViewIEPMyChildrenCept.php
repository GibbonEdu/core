<?php
/**
 * @covers modules/Individual Needs/iep_view_myChildren.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View IEP for my children');
$I->loginAsParent();

// Get current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Individual Needs', 'iep_view_myChildren.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->dontSeeErrors();
