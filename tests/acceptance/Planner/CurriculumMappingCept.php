<?php
/**
 * @covers modules/Planner/curriculumMapping_outcomesByCourse.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('check Outcomes By Course');
$I->loginAsAdmin();

$I->amOnModulePage('Planner', 'curriculumMapping_outcomesByCourse.php');
$I->seeBreadcrumb('Outcomes By Course');

// Basic Check -----------------------------------------

$I->dontSeeErrors();

// Filter Test -----------------------------------------

$I->selectFromDropdown('gibbonCourseID', 1);
$I->submitForm('#searchForm', []);
$I->dontSeeErrors();
