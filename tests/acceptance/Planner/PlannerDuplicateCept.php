<?php
/**
 * @covers modules/Planner/planner_duplicate.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Duplicate Lesson Plan');
$I->loginAsAdmin();

// Get a course class and planner entry
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonPlannerEntryID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

$I->amOnModulePage('Planner', 'planner_duplicate.php', [
    'viewBy' => 'class',
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID
]);
$I->seeBreadcrumb('Duplicate Lesson Plan');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
