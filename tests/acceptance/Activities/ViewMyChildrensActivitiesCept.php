<?php
/**
 * @covers modules/Activities/activities_view_myChildren.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View my children\'s activities');
$I->loginAsParent();

// Get the current school year
$gibbonSchoolYearID = $I->grabFromDatabase('gibbonSchoolYear', 'gibbonSchoolYearID', ['status' => 'Current']);

$I->amOnModulePage('Activities', 'activities_view_myChildren.php', ['gibbonSchoolYearID' => $gibbonSchoolYearID]);
$I->seeBreadcrumb('View Activities');
