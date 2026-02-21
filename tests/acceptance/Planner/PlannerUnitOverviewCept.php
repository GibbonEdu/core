<?php
/**
 * @covers modules/Planner/planner_unitOverview.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Unit Overview');
$I->loginAsAdmin();

// Get a planner entry
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonPlannerEntryID', []);

$I->amOnModulePage('Planner', 'planner_unitOverview.php', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'viewBy' => 'date'
]);
$I->seeBreadcrumb('Unit Overview');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
