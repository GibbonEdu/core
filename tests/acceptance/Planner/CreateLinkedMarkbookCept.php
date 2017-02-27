<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('create a lesson with a linked markbook column');
$I->loginAsAdmin();
$I->amOnPage('/index.php?q=/modules/Planner/planner.php');

// Add Lesson ------------------------------------------------
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Lesson Plan');

$date = $I->grabAttributeFrom('#date', 'value');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->fillField('name', 'Testing Markbook');
$I->fillField('timeStart', '09:00');
$I->fillField('timeEnd', '10:00');
$I->selectOption('markbook', 'Y');
$I->click('Submit');

// Verify Linked Lesson ---------------------------------------

$I->see('Planner was successfully added', '.success');
$I->seeBreadcrumb('Add Column');

$gibbonPlannerEntryID = $I->grabValueFromURL('gibbonPlannerEntryID');
$I->seeInField('gibbonPlannerEntryID', $gibbonPlannerEntryID);
$I->seeInField('name', 'Testing Markbook');

// Add Column ------------------------------------------------

$I->fillField('description', 'Linked to Planner Lesson');
$I->selectFromDropdown('type', 2);
$I->seeInField('date', $date);
$I->selectOption('attainment', 'Y');
$I->selectOption('effort', 'N');
$I->selectOption('viewableStudents', 'N');
$I->selectOption('viewableParents', 'N');
$I->click('Submit');

// Verify Column ------------------------------------------------

$I->see('Your request was completed successfully.', '.success');
$I->click('here', 'a');
$I->seeBreadcrumb('Edit Column');

$gibbonMarkbookColumnID = $I->grabValueFromURL('gibbonMarkbookColumnID');
$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

$I->seeInField('name', 'Testing Markbook');
$I->seeInField('description', 'Linked to Planner Lesson');
$I->seeInField('attainment', 'Y');
$I->seeInField('effort', 'N');
$I->seeInField('viewableStudents', 'N');
$I->seeInField('viewableParents', 'N');

// Cleanup Markbook ------------------------------------------------

$I->amOnPage('/index.php?q=/modules/Markbook/markbook_edit_delete.php&.php&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonMarkbookColumnID='.$gibbonMarkbookColumnID);
$I->seeBreadcrumb('Delete Column');
$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Cleanup Planner ------------------------------------------------

$I->amOnPage('/index.php?q=/modules/Planner/planner_delete.php&gibbonPlannerEntryID='.$gibbonPlannerEntryID);
$I->seeBreadcrumb('Delete Lesson Plan');
$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');
