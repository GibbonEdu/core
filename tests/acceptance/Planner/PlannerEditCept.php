<?php
/**
 * @covers modules/Planner/planner_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Edit Lesson Plan');
$I->loginAsAdmin();

// Get a course class and planner entry
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);
$gibbonPlannerEntryID = $I->grabFromDatabase('gibbonPlannerEntry', 'gibbonPlannerEntryID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

// If no planner entry exists for this class, skip the test
if (!$gibbonPlannerEntryID) {
    $I->comment('Skipping: No planner entry found for this course class');
    return;
}

$I->amOnModulePage('Planner', 'planner_edit.php', [
    'viewBy' => 'class',
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID
]);
$I->seeBreadcrumb('Edit');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
