<?php
/**
 * @covers modules/Markbook/markbook_view_rubric.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('view markbook rubric');
$I->loginAsAdmin();

// Get a course class ID from the database
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);

if (empty($gibbonCourseClassID)) {
    $I->comment('No course classes found, skipping rubric test');
    return;
}

// Get a markbook column with a rubric
$gibbonMarkbookColumnID = $I->grabFromDatabase('gibbonMarkbookColumn', 'gibbonMarkbookColumnID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

if (empty($gibbonMarkbookColumnID)) {
    $I->comment('No markbook columns found, skipping rubric test');
    return;
}

// Get the rubric ID from the column
$gibbonRubricID = $I->grabFromDatabase('gibbonMarkbookColumn', 'gibbonRubricIDAttainment', [
    'gibbonMarkbookColumnID' => $gibbonMarkbookColumnID
]);

if (empty($gibbonRubricID)) {
    $I->comment('No rubric found for this column, skipping rubric test');
    return;
}

// Get a student from the class
$gibbonPersonID = $I->grabFromDatabase('gibbonCourseClassPerson', 'gibbonPersonID', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'role' => 'Student'
]);

if (empty($gibbonPersonID)) {
    $I->comment('No students found in this class, skipping rubric test');
    return;
}

// Test Rubric View ------------------------------------

$I->amOnPage('/fullscreen.php?q=/modules/Markbook/markbook_view_rubric.php&gibbonRubricID='.$gibbonRubricID.'&gibbonCourseClassID='.$gibbonCourseClassID.'&gibbonMarkbookColumnID='.$gibbonMarkbookColumnID.'&gibbonPersonID='.$gibbonPersonID.'&mark=FALSE&type=attainment&width=1100&height=550');
$I->dontSeeErrors();
