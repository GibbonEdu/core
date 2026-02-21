<?php
/**
 * @covers modules/Formal Assessment/internalAssessment_write.php
 * @covers modules/Formal Assessment/internalAssessment_write_data.php
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Write internal assessments');
$I->loginAsAdmin();
$I->amOnModulePage('Formal Assessment', 'internalAssessment_write.php');
$I->seeBreadcrumb('Write Internal Assessments');

// Test Write Data page --------------------------------

// Get a course class ID and internal assessment column ID
$gibbonCourseClassID = $I->grabFromDatabase('gibbonCourseClass', 'gibbonCourseClassID', []);
$gibbonInternalAssessmentColumnID = $I->grabFromDatabase('gibbonInternalAssessmentColumn', 'gibbonInternalAssessmentColumnID', [
    'gibbonCourseClassID' => $gibbonCourseClassID
]);

$I->amOnModulePage('Formal Assessment', 'internalAssessment_write_data.php', [
    'gibbonCourseClassID' => $gibbonCourseClassID,
    'gibbonInternalAssessmentColumnID' => $gibbonInternalAssessmentColumnID
]);

$I->dontSeeErrors();