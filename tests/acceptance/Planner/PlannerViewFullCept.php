<?php
/**
 * @covers modules/Planner/planner_view_full.php
 * @covers modules/Planner/planner_view_full_post.php
 * @covers modules/Planner/planner_view_full_submit_edit.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check View Lesson Plan');
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

$I->amOnModulePage('Planner', 'planner_view_full.php', [
    'viewBy' => 'class',
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonPlannerEntryID' => $gibbonPlannerEntryID
]);
$I->seeBreadcrumb('View Lesson Plan');

// Basic Check -----------------------------------------

$I->dontSeeErrors();
