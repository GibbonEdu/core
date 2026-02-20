<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('view crowd assessment discussion');
$I->loginAsAdmin();

// Get a planner entry homework record
$gibbonPlannerEntryHomeworkID = $I->grabFromDatabase('gibbonPlannerEntryHomework', 'gibbonPlannerEntryHomeworkID', []);
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntryHomework', 'gibbonPlannerEntryID', ['gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID]);
$gibbonPersonID = $I->grabFromDatabase('gibbonPlannerEntryHomework', 'gibbonPersonID', ['gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID]);

$I->amOnModulePage('Crowd Assessment', 'crowdAssess_view_discuss.php', [
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID,
    'gibbonPersonID' => $gibbonPersonID,
    'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID
]);
$I->seeBreadcrumb('Discuss');
$I->see('Student');
