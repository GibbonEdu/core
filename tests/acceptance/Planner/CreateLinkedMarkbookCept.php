<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('create a lesson with a linked markbook column');
$I->loginAsAdmin();
$I->amOnModulePage('Planner', 'planner_add.php');

// Add Lesson ------------------------------------------------
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
$I->seeInField('gibbonPlannerEntryID', str_pad($gibbonPlannerEntryID, 14, '0', STR_PAD_LEFT));
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

// Delete Markbook -----------------------------------------------

$urlParams = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID);
$I->amOnModulePage('Markbook', 'markbook_edit_delete.php', $urlParams );

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Delete Planner ------------------------------------------------

$urlParams = array('gibbonCourseClassID' => $gibbonCourseClassID, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'viewBy' => 'class');
$I->amOnModulePage('Planner', 'planner_delete.php', $urlParams );

$I->click('Yes');
$I->see('Your request was completed successfully.', '.success');

// Force Cleanup (for failed tests) ------------------------------

$I->deleteFromDatabase('gibbonMarkbookColumn', ['gibbonMarkbookColumnID' => $gibbonMarkbookColumnID]);
$I->deleteFromDatabase('gibbonPlannerEntry', ['gibbonPlannerEntryID' => $gibbonPlannerEntryID]);
