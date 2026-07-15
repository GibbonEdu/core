<?php
/**
 * @covers modules/Planner/planner.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('View planner');
$I->loginAsAdmin();

// Get a course class
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);

$I->amOnModulePage('Planner', 'planner.php', ['gibbonCourseClassID' => $gibbonCourseClassID]);
$I->seeBreadcrumb('Planner');
