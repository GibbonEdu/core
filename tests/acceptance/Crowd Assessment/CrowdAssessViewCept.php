<?php
/**
 * @covers modules/Crowd Assessment/crowdAssess_view.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view a specific crowd assessment');
$I->loginAsAdmin();

// Get a planner entry with homework crowd assess enabled
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonPlannerEntryID', ['homeworkCrowdAssess' => 'Y']);

$I->amOnModulePage('Crowd Assessment', 'crowdAssess_view.php', ['gibbonPlannerEntryID' => $gibbonPlannerEntryID]);
$I->seeBreadcrumb('View Assessment');
$I->see('Class');
$I->see('Name');
