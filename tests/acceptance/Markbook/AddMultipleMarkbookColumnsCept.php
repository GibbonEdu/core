<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_edit_addMulti.php
 * @covers modules/Markbook/markbook_edit.php
 * @covers modules/Markbook/markbook_edit_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('add multiple markbook columns across classes');
$I->loginAsAdmin();

// Navigate to Markbook Edit page
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#searchForm');

$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

// Add Multiple Columns ------------------------------------------------
$I->amOnModulePage('Markbook', 'markbook_edit_addMulti.php', array('gibbonCourseClassID' => $gibbonCourseClassID));
$I->seeBreadcrumb('Add Multiple Columns');

// Select multiple classes (including current class)
$I->selectFromDropdown('gibbonCourseClassIDMulti', 1);
$I->selectFromDropdown('gibbonCourseClassIDMulti', 2);

$formValues = array(
    'name'                     => 'Multi Test Column',
    'description'              => 'This is a multi-class test column.',
    'type'                     => 'Homework',
    'attainment'               => 'N',
    'effort'                   => 'N',
    'comment'                  => 'N',
    'uploadedResponse'         => 'N',
    'viewableStudents'         => 'N',
    'viewableParents'          => 'N',
);

$I->submitForm('#content form', $formValues, 'Submit');
$I->seeSuccessMessage();

// Verify column was created by navigating back to markbook edit
$I->amOnModulePage('Markbook', 'markbook_edit.php', array('gibbonCourseClassID' => $gibbonCourseClassID));
$I->see('Multi Test Column');

// Get the column ID for cleanup
$gibbonMarkbookColumnID = $I->grabFromDatabase('gibbonMarkbookColumn', 'gibbonMarkbookColumnID', array('name' => 'Multi Test Column', 'gibbonCourseClassID' => $gibbonCourseClassID));

// Clean up - Delete the created column
$I->amOnModulePage('Markbook', 'markbook_edit_delete.php', array(
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID
));

$I->click('Delete');
$I->seeSuccessMessage();
