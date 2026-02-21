<?php
/**
 * @covers modules/Markbook/markbook_view.php
 * @covers modules/Markbook/markbook_edit_add.php
 * @covers modules/Markbook/markbook_edit.php
 * @covers modules/Markbook/markbook_edit_copy.php
 * @covers modules/Markbook/markbook_edit_delete.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view markbook column copy page');
$I->loginAsAdmin();

// Create a test column in the first class
$I->amOnModulePage('Markbook', 'markbook_view.php');
$I->seeBreadcrumb('View Markbook');

$I->selectFromDropdown('gibbonCourseClassID', 2);
$I->click('Go', '#searchForm');

$gibbonCourseClassID = $I->grabValueFromURL('gibbonCourseClassID');

// Add a column
$I->clickNavigation('Add');
$I->seeBreadcrumb('Add Column');

$formValues = array(
    'name'                     => 'Column to Copy',
    'description'              => 'This column will be copied.',
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

$gibbonMarkbookColumnID = $I->grabEditIDFromURL();

// Navigate to Edit Markbook page
$I->amOnModulePage('Markbook', 'markbook_edit.php', array('gibbonCourseClassID' => $gibbonCourseClassID));

// Verify the copy form exists
$I->see('Copy Markbook Columns');
$I->seeElement('select[name="gibbonMarkbookCopyClassID"]');

// Test Copy Action ------------------------------------

// Select a different class to copy to
$I->selectFromDropdown('gibbonMarkbookCopyClassID', 1);

// Submit the copy form
$I->submitForm('#content form', [], 'Submit');

// Should redirect to markbook_edit_copy.php
$I->seeInCurrentUrl('markbook_edit_copy.php');
$I->dontSeeErrors();

// Clean up - Delete the column
$I->amOnModulePage('Markbook', 'markbook_edit_delete.php', array(
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID
));

$I->click('Delete');
$I->seeSuccessMessage();

